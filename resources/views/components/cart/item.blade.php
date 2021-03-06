@props(['cartItem'])

<div class="d-sm-flex justify-content-between align-items-center my-2 pb-3 border-bottom">
   <div class="d-block d-sm-flex align-items-center text-center text-sm-start">
      <a class="d-inline-block flex-shrink-0 mx-auto me-sm-4"
         href="{{ route('products.show', ['product' => $cartItem->product->slug]) }}">
         <img src="{{ $cartItem->product->first_image }}"
            width="160"
            alt="{{ $cartItem->product->title }}">
      </a>
      <div class="pt-2">
         <h3 class="product-title fs-base mb-2"><a
               href="{{ route('products.show', ['product' => $cartItem->product->slug]) }}">{{ $cartItem->product->title }}</a>
         </h3>
         @if ($cartItem->product->id)
            @foreach ($cartItem->selected_options as $attributeName => $selectedOption)
               <div class="fs-sm">
                  <span
                     class="text-muted me-2">{{ $cartItem->product->getAttributeByName($attributeName)->label }}:
                  </span>
                  @if (isset($selectedOption['ref']))
                     {{ $cartItem->product->getOptionByRef($attributeName, $selectedOption['ref'])['name'] }}
                  @else
                     @if (is_array($selectedOption['value']) && count($selectedOption['value']))
                        @foreach ($selectedOption['value'] as $groupName => $selectedValue)
                           <span>{{ $groupName }} : {{ $selectedValue }}</span>
                           @if (!$loop->last)
                              &#xd7;
                           @endif
                        @endforeach
                     @else
                        {{ $selectedOption['value'] }}
                     @endif
                  @endif
               </div>
            @endforeach
         @endif
         <div class="fs-lg text-accent pt-2">{{ format_price($cartItem->subtotal) }} <small>Dhs
               HT</small></div>
      </div>
   </div>
   <div class="pt-2 pt-sm-0 ps-sm-3 mx-auto mx-sm-0 text-center text-sm-start"
      style="max-width: 9rem;">
      <label class="form-label">{{ __('Quantity') }}</label>
      <span>{{ $cartItem->quantity }}</span>
      <button onclick="confirm('{{ __('Are you sure ?') }}')
         || event.stopImmediatePropagation()"
         wire:click="removeCartItem({{ $cartItem->id }})"
         class="btn btn-link px-0 text-danger pb-0"
         type="button">
         <i class="ci-close-circle me-2"></i>
         <span class="fs-sm">{{ __('Remove') }}</span>
      </button>
      <a href="{{ route('products.show', ['product' => $cartItem->product->slug, 'cartItemId' => $cartItem->id]) }}"
         class="btn btn-link px-0 text-success">
         <i class="ci-edit
         me-2"></i>
         <span class="fs-sm">{{ __('Edit') }}</span>
      </a>
   </div>
</div>
