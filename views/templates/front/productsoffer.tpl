{if $productsOffer}
    <div id="products-offer">
        <section class="featured-products ">
            <h3 class="h1 products-section-title">{l s='Ofertas' mod='productsoffer'}</h3>
            <div class="products">
                {foreach from=$productsOffer item="product"}
                    {include file="catalog/_partials/miniatures/product.tpl"}
                {/foreach}
            </div>
        </section>
    </div>
{/if}