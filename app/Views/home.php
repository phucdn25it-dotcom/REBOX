<?php
$homeLocations = array_map(static fn(array $location): array => [
    'name' => (string)($location['name'] ?? ''),
    'address' => (string)($location['address'] ?? ''),
    'phone' => (string)($location['phone'] ?? ''),
    'opening_hours' => (string)($location['opening_hours'] ?? ''),
    'accepts_returns' => (bool)($location['accepts_returns'] ?? true),
], $locations ?? []);
$homeProducts = array_map(static fn(array $product): array => [
    'id' => (int)($product['id'] ?? 0),
    'name' => (string)($product['name'] ?? ''),
    'image_url' => image_url($product['image_url'] ?? ''),
    'category' => (string)($product['category'] ?? ''),
    'material' => (string)($product['material'] ?? ''),
    'capacity_ml' => (int)($product['capacity_ml'] ?? 0),
    'rental_price' => (float)($product['rental_price'] ?? 0),
    'deposit_amount' => (float)($product['deposit_amount'] ?? 0),
    'available' => (int)($product['available'] ?? 0),
], $products ?? []);
$locationsParam = rawurlencode((string)json_encode($homeLocations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$homeProductsJson = json_encode($homeProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$cartQuantity = array_sum(array_map('intval', $_SESSION['cart'] ?? []));
$currentUser = $user ?? null;
$userDashboard = match($currentUser['role_code'] ?? '') {
    'CUSTOMER' => '/account', 'STAFF' => '/staff', 'PARTNER' => '/partner', 'ADMIN' => '/admin', default => '/account',
};
$authParam = rawurlencode((string)json_encode([
    'signed_in' => (bool)$currentUser,
    'name' => (string)($currentUser['name'] ?? ''),
    'dashboard' => $userDashboard,
    'role' => (string)($currentUser['role_code'] ?? ''),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
?>
<iframe id="rebox-home-frame" class="kage-frame" src="<?=e(url('/landing-pages/kage.html?locations=' . $locationsParam . '&cart=' . $cartQuantity . '&auth=' . $authParam . '&v=20261019-8'))?>" title="ReBox — dùng lại thật nhẹ nhàng" allow="autoplay; fullscreen" loading="eager"></iframe>
<script>
const reboxHomeProducts = <?= $homeProductsJson ?: '[]' ?>;
const reboxHomeFrame = document.getElementById('rebox-home-frame');
function sendReboxHomeProducts() {
    reboxHomeFrame.contentWindow.postMessage({ type: 'rebox:home-data', products: reboxHomeProducts }, window.location.origin);
}
reboxHomeFrame.addEventListener('load', sendReboxHomeProducts);
if (reboxHomeFrame.contentDocument?.readyState === 'complete') sendReboxHomeProducts();
</script>
<form id="home-rent-form" method="post" action="<?=e(url('/cart/add'))?>" hidden>
    <?=csrf_field()?>
    <input type="hidden" name="product_id" value="">
    <input type="hidden" name="quantity" value="1">
    <input type="hidden" name="return_to" value="home">
</form>
<form id="home-logout-form" method="post" action="<?=e(url('/logout'))?>" hidden><?=csrf_field()?></form>
<script>
window.addEventListener('message', function (event) {
    if (event.origin !== window.location.origin || !event.data) return;
    if (event.data.type === 'rebox:add-to-cart') {
        const productId = Number(event.data.productId);
        if (!Number.isSafeInteger(productId) || productId < 1) return;
        const form = document.getElementById('home-rent-form');
        form.elements.product_id.value = String(productId);
        form.requestSubmit();
    } else if (event.data.type === 'rebox:open-product') {
        const productId = Number(event.data.productId);
        if (Number.isSafeInteger(productId) && productId > 0) window.location.href = <?=json_encode(url('/product?id='))?> + encodeURIComponent(String(productId));
    } else if (event.data.type === 'rebox:logout') {
        document.getElementById('home-logout-form').requestSubmit();
    }
});
</script>
