<?php
  // php
  settings_fields('humun');
  $humunAPI = $_ENV["HUMUN_API"];

  if ($humunAPI == "") {
      $humunAPI = "https://humun.us/api/v1";
  }

function getWooProducts() {
    $args = array();
    $prodString = "";
    $products = wc_get_products( $args );
    
    // loop through products and create a div for each
    foreach ( $products as $product ) {
      $product_id = $product->get_id();
      $product_name = $product->get_name();
      $product_price = $product->get_price();
      $product_url = $product->get_permalink();
      $product_image = $product->get_image();
      $product_description = $product->get_description();
      $humun_id = $product->get_meta('humun_id');
        $prodString .= '<div class="humun-woo-product" id="'.$product_id.'" data-humun-id="'.$humun_id.'" >';
        $prodString .= '<div class="item-image">';
        $prodString .=  $product_image ;
        $prodString .= '</div>';
        $prodString .= '<div class="item-name">';
        $prodString .=  $product_name ;
        $prodString .= '</div>';
        $prodString .= '<div class="item-price">';
        $prodString .= $product_price ;
        $prodString .= '</div>';
        $prodString .= '</div>';
    }
    return $prodString;
}

function updateProductMeta($product_id, $humun_id) {
    update_post_meta($product_id, 'humun_id', $humun_id);
}

if ($_POST['humun_woo_action'] == 'update_humun_id') {
    $humun_id = $_POST['humun_id'];
    $product_id = $_POST['product_id'];
    updateProductMeta($product_id, $humun_id);
}

$parse = parse_url(get_site_url());
$shop = $parse['host'];
$shop = get_site_url();
$humun_auth_url = $humunAPI . '/auth/woocommerce/auth?shop=' . $shop;

?>


<h1 class="title" id="humun-admin-page-title">humun</h1>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poiret+One&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">
<link rel="stylesheet" href="/wp-content/plugins/woo-humun/style.css">
<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
<script src="https://js.stripe.com/v3/"></script>
<script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>


<body>
    <div id="">
            <div id="set_tenant">
            <form method="post" action="options.php">
                <?php settings_fields('humun'); ?>
                <input type="text" id="humun_tenant_name" name="tenant" placeholder="humun tenant" value="<?php echo get_option('tenant'); ?>">
                <?php submit_button(); ?>
                <script>
                    window.HumunTenant = '<?php echo get_option('tenant'); ?>';
                </script>
            </form>
 
            <!--
                <input type="text" id="humun_tenant_name" placeholder="humun tenant" v-model="tenantID">
                <button class="button primary" @click="setTenant">save tenant</button>
            -->

            </div>

            <div id='connect_humun'>
                <button class="button primary" >
                    <a href="<?php echo $humun_auth_url ?>">Connect to humun</a>
                </button>
            </div>

        <hr>

        <h2>Link Products</h2>

        <div id="humun_app">
            <div id="humun-products">
                <div class="humun-product" v-for="item in items">
                    <div class="item-image">
                        <img :src="item.Image" alt="">
                    </div>
                    <div class="item-details">
                        <b>{{ item.Name }}</b>
                        <p>{{ item.Description }}</p>
                        <p>${{ item.Price }}</p>
                        <small>{{ item.Tenant }}</small>
                    </div>
                    <div class="item-actions">
                        <button class="button primary" @click="linkItem(item)" v-if="!linkedWooItem(item)">Link Item</button>
                        <button class="button primary" @click="unlinkItem(linkedWooItem(item))" v-if="linkedWooItem(item)">Linked with {{linkedWooItemName(item)}}</button>
                    </div>
                </div>
            </div>


            <div id="select_woo_item">
                
                <div class="modal is-active" v-if="showSelectItem">
                    <div class="modal-background"></div>
                    <div class="modal-card">
                        <header class="modal-card-head">
                        <p class="modal-card-title">Select item</p>
                        <button class="delete" aria-label="close" @click="showSelectItem = false"></button>
                        </header>
                        <section class="modal-card-body">
                                <table id="woo_item_selector">
                            <tr class="humun_woo_item_select" v-for="item in wooItems">
                                <td>
                                    <img class="wooImageSelect" :src="item.image" alt="">
                                </td>
                                <td>
                                    <p><b>{{item.name}}</b></p>
                                    <p>${{item.price}}</p>
                                </td>
                                <td>
                                    <button class="button is-success" @click="selectLinkItem(item)">Select</button>
                                </td>
                            </tr>
                        </table>
                        </section>
                        <footer class="modal-card-foot">
                        <button class="button" @click="showSelectItem = false">Cancel</button>
                        </footer>
                    </div>
                </div>
            </div>

            <form method="post" id="updateProductLink" action="">
                <input type="hidden" name="humun_woo_action" value="update_humun_id">
                <input type="text" name="product_id" v-model="link.product_id">
                <input type="text" name="humun_id" v-model="link.humun_id">
            </form>
            <div id="woo-products">
                <?php echo getWooProducts(); ?>
            </div>
        </div>
    </div>
</body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.4/axios.min.js" integrity="sha512-lTLt+W7MrmDfKam+r3D2LURu0F47a3QaW5nF0c6Hl0JDZ57ruei+ovbg7BrZ+0bjVJ5YgzsAWE+RreERbpPE1g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://humun.us/js/humun-v0.0.2.js"></script>
<script src="/wp-content/plugins/woo-humun/scripts/app.js"></script>