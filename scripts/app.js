Vue.prototype.Humun = Humun
var app = new Vue({
    el: '#humun_app',
    data: {
        status: '',
        state: {},
        tenantID: '',
        link: {
            humun_id: '',
            product_id: '',
        },
        items: [],
        wooItems: [],
        showSelectItem: false,
    },
    methods: {
        stateFx(s) {
            console.log("state-change", s)
            this.status = s;
            if (s.state == 'checkout-complete') {
                this.view = 'thank-you';
            }
        },
        setTenant() {
            console.log("setTenant", this.tenantID)
            Humun.tenantID = this.tenantID;
            Humun.getItems(1, 100)
                .then(res => {
                    this.items = res.data;
                })
                .catch(err => {
                    console.log("error", err)
                })
        },
        linkedWooItem(item) {
            for (let w in this.wooItems) {
                if (this.wooItems[w].humun_id == item.ID) {
                    return this.wooItems[w];
                }
            }
            return false;
        },
        linkedWooItemName(item) {
            let wooItem = this.linkedWooItem(item);
            if (wooItem) {
                return wooItem.name;
            }
            return "";
        },
        unlinkItem(wooItem) {
            this.link.product_id = wooItem.id;
            this.link.humun_id = "";
            setTimeout(function() {
                document.querySelector('#updateProductLink').submit();
            }, 100);
        },
        linkItem(item) {
            console.log("linkItem", item)
            this.link.humun_id = item.ID;
            this.showSelectItem = !this.showSelectItem;
            console.log("link", this.link)
        },
        selectLinkItem(item) {
            console.log("selectLinkItem", item)
            this.link.product_id = item.id;
            setTimeout(function() {
                document.querySelector('#updateProductLink').submit();
            }, 100);
            //this.showSelectItem = !this.showSelectItem;
            console.log("link", this.link)
        },
        suckWooDOM() {
            var wooItems = []
            var woo = document.querySelector("#woo-products")
            var woo_items = woo.querySelectorAll(".humun-woo-product")
            for (var i = 0; i < woo_items.length; i++) {
                let wi = {
                    image: woo_items[i].querySelector(".item-image img").src,
                    name: woo_items[i].querySelector(".item-name").innerText,
                    price: woo_items[i].querySelector(".item-price").innerText,
                    id: woo_items[i].id,
                    humun_id: woo_items[i].getAttribute("data-humun-id"),
                }
                wooItems.push(wi)
            }
            this.wooItems = wooItems
            return wooItems
        },
        init() {
            if (window.location.host.indexOf("dev.humun") !== -1 || window.location.host.indexOf("localhost") !== -1) {
                Humun.apiBase = 'https://dev.humun.us/api/v1'
            } else {
                Humun.apiBase = 'https://humun.us/api/v1'
            }
            if (window.HumunTenant) {
                this.tenantID = window.HumunTenant;
            }
            Humun.Tenant(this.tenantID)
            Humun.StateFx = this.stateFx.bind(this)
            Humun.Init().then((res) => {
                this.items = res.data
            }).catch((err) => {
                console.log(err)
            })
        }
    },
    mounted () {
        this.init()
        this.suckWooDOM()
    }

})
