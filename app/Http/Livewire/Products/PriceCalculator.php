<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use Livewire\Component;
use App\Models\CartItem;
use App\Services\CartService;
use Livewire\WithFileUploads;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PriceCalculator extends Component
{
    use WithFileUploads, AuthorizesRequests;

    public Product $product;

    /**
     * the user selected options
     * ex : [
     *  "size" => "optionRef",
     *  "material" => "optionRef"
     * ]
     * @var Collection
     */
    public Collection $selectedOptions;

    /**
     * The selected options required files
     *  ex : [
     *   "recto-verso" => ["livewirefile1" ,  "livewirefile2"],
     *   "verso" => ["livewirefile1"]
     * ]
     * @var array
     */
    public array $requiredFiles = [];

    public int|string $selectedQuantityValue;

    public bool $designByCompany = false;

    public string $designInformation = "";

    public $designFiles = [];

    public float $unitPrice;

    public float $totalPrice;

    /**
     * In case of edit mode we need the following properties
     */
    public bool $editMode = false;

    private CartItem $cartItem;

    public Collection $oldMedia;

    public array $oldMediaIdsToDelete = [];


    protected function rules()
    {
        $fileRules = ["file", "max:10240", "mimes:jpg,jpeg,png,gif,eps,ai,svg,pdf,zip,tar,rar,cdr,psd"];

        return [
            'selectedOptions' => 'nullable',
            "selectedQuantityValue" => "required|numeric",
            'designByCompany' => "required|boolean",
            "totalPrice" => "required|numeric|min:1",
            "requiredFiles.*" => ["nullable", $fileRules],
            "designFiles.*" => ["nullable", $fileRules]
        ];
    }

    public function render()
    {
        return view('livewire.products.price-calculator');
    }

    public function mount()
    {
        $cartItem = CartItem::find(request("cartItemId"));

        /**Check if we are in edit mode */
        if ($cartItem) {
            $this->editMode = true;
            $this->cartItem = $cartItem;
            $this->oldMedia = $cartItem->media;
            $this->selectedOptions = $cartItem->selected_options;
            $this->selectedQuantityValue = $cartItem->quantity;
            $this->designByCompany = $cartItem->design_by_company;
            $this->designInformation = $cartItem->design_information;
        } else {
            $this->oldMedia = collect([]);
            $this->selectedOptions = collect([]);
            $this->product->attributs->each(function ($attribute) {
                $this->selectedOptions->put($attribute->name, $attribute->pivot->options[0]["ref"]);
            });
            $this->selectedQuantityValue = $this->product->allowed_quantities[0]["value"];
        }

        $this->calculatePrices();

        $this->findAndSetRequiredFiles();
    }

    public function updatedSelectedOptions()
    {
        $this->calculatePrices();

        $this->findAndSetRequiredFiles();
    }

    public function updatedSelectedQuantityValue()
    {
        $this->calculatePrices();
    }

    public function updatedDesignByCompany()
    {
        $this->calculatePrices();
    }

    public function updatedRequiredFiles($newFile, $requiredFileName)
    {
        if ($this->editMode) {
            $mediaItem = $this->oldMedia->where("name", $requiredFileName)->first();

            if ($mediaItem) {
                $this->deleteOldMediaItemLocally($mediaItem->id);
            }
        }
    }

    /**
     * Calculates the total and unit price
     *
     * @return void
     */
    private function calculatePrices(): void
    {
        $selectedOptionsTotalPrice = $this->selectedOptions->reduce(function ($carry, $optionRef, $attributeName) {
            $option = $this->product->getOptionByRef($attributeName, $optionRef);

            /** in case a millisious user tries to tamper with the option */
            if (!$option) {
                $this->mount();
                return;
            }

            return $carry + $option["price"];
        }, 0);

        $quantity = collect($this->product->allowed_quantities)->where("value", $this->selectedQuantityValue)->first();

        /** in case a millisious user tries to tamper with the quantity */
        if (!$quantity) {
            $this->mount();
            return;
        }

        $this->totalPrice = $quantity["price"] + ($selectedOptionsTotalPrice * $quantity["value"]);

        if ($this->designByCompany) {
            $this->totalPrice += $this->product->design_price;
        }

        $this->unitPrice = $this->totalPrice / $quantity["value"];
    }

    /**
     * Loops through every option and set the corresponding required files array
     *
     * @return void
     */
    public function findAndSetRequiredFiles(): void
    {
        $this->requiredFiles = [];
        
        $this->selectedOptions->each(function ($optionRef, $attributeName) {
            $option = $this->product->getOptionByRef($attributeName, $optionRef);

            if (isset($option['requiredFilesProperties'])) {
                foreach ($option['requiredFilesProperties'] as $requiredFileProperties) {
                    $this->requiredFiles[$requiredFileProperties["name"]] = "";
                }
            }
        });

        if (!count($this->requiredFiles)) {
            $this->requiredFiles["recto"] = "";
        }
    }

    /**
     * Delete the media item from the component only
     *
     * @param int|string $mediaItemId
     * @return void
     */
    public function deleteOldMediaItemLocally(string $mediaItemId): void
    {
        if (!in_array($mediaItemId,  $this->oldMediaIdsToDelete)) {
            $this->oldMediaIdsToDelete[] = $mediaItemId;

            $this->oldMedia = $this->oldMedia->filter(function ($mediaItem) use ($mediaItemId) {
                return $mediaItem->id != $mediaItemId;
            });
        }
    }

    /**
     * Validate the requiredFiles and designFiles
     *
     * @param Illuminate\Validation\Validator
     * @return void
     */
    public function withValidatorClosure(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->designByCompany) {
                if (count($this->designFiles) + count($this->oldMedia)  > 5) {
                    $validator->errors()->add("designFiles", __("5 files is the maximum allowed"));
                }
            } else {
                foreach ($this->requiredFiles as $requiredFileName => $file) {
                    if (!$file && !$this->oldMedia->where("name", $requiredFileName)->first()) {
                        $validator->errors()->add($requiredFileName, __("validation.required", [
                            "attribute" => $requiredFileName
                        ]));
                    }
                }
            }
        });
    }

    /**
     * Add item to cart
     *
     * @return void
     */
    public function addToCart(): void
    {
        $this->withValidator(fn (Validator $validator) => $this->withValidatorClosure($validator))->validate();

        // dd("validation succeded");
        if ($this->editMode) {
            // Edit the cart item and upload new files and delete old media
        } else {
            $success = CartService::addItemToCart(
                [
                    "quantity" => $this->selectedQuantityValue,
                    "subtotal" => $this->totalPrice,
                    "selected_options" => $this->selectedOptions,
                    "design_by_company" => $this->designByCompany,
                    "design_information" => $this->designInformation,
                    "product_id" => $this->product->id
                ],
                $this->designByCompany ? $this->designFiles : $this->requiredFiles
            );

            if ($success) {
                redirect()->route("cart.index");
            } else {
                session()->flash(
                    "error_message",
                    __("Unknown error occurred. our team has been notified. try again !")
                );
            }
        }
    }
}
