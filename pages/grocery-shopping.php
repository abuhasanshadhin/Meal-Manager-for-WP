<?php
require '../includes/functions.php';
require '../includes/header.php';
?>

<div id="mm-app" class="row mt-2 no-gutters">
    <div class="col-md-5">
        <div class="bg-light px-3 py-2">
            <h5 class="mt-2 mb-3">
                <template v-if="grocery_id">Edit Grocery Shopping</template>
                <template v-if="!grocery_id">Add Grocery Shopping</template>
            </h5>

            <div :style="{display: error || success ? '' : 'none'}" style="display:none">
                <div v-if="error" class="error mb-2">{{ error }}</div>
                <div v-if="success" class="updated mb-2">{{ success }}</div>
            </div>

            <form @submit.prevent="saveGrocery" method="post">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date"> Date <span class="text-danger">*</span> </label>
                            <input type="date" v-model="grocery.date" id="date" class="form-control" style="height:31px">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="member"> Member <span class="text-danger">*</span> </label>
                        <select v-model="grocery.member_id" id="member" class="form-control">
                            <option value=""> -- Select Member -- </option>
                            <?php
                                $members = $mm_db->query("SELECT * FROM mm_members WHERE deleted_at IS NULL");
                                while ($row = $members->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                            ?>
                        </select> 
                    </div>
                </div>

                <div class="form-group">
                    <label for="address"> Grocery Items </label>
                    <div class="d-flex">
                        <input type="text" v-model.trim="groceryItem.item_name" placeholder="Item name" class="form-control">
                        <input type="text" v-model.trim="groceryItem.quantity" placeholder="Quantity" class="form-control mx-1">
                        <input type="number" v-model.number="groceryItem.amount" placeholder="Amount" class="form-control mr-1">
                        <button type="button" @click.prevent="addGroceryItem" class="btn btn-primary btn-sm">Add</button>
                    </div>
                </div>

                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-sm text-center mb-0">
                        <thead>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </thead>
                        <tbody :style="{display: grocery.grocery_items.length ? '' : 'none'}" style="display:none">
                            <tr v-for="(item, i) in grocery.grocery_items" :key="i">
                                <td><input type="text" v-model="item.item_name" style="height:31px"></td>
                                <td><input type="text" v-model.trim="item.quantity" style="width:70px; height:31px"></td>
                                <td><input type="number" v-model.number="item.amount" style="width:100px; height:31px"></td>
                                <td>
                                    <button 
                                        type="button" 
                                        @click.prevent="removeItem(i)" 
                                        class="btn btn-danger btn-sm"
                                    >
                                        X
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="form-group">
                    <div class="text-right" :style="{display: grocery.grocery_items.length ? '' : 'none'}" style="display:none">
                        <b>Total Amount : </b> {{ (grocery.grocery_items.reduce((p, c) => +p + +c.amount, 0)).toFixed(2) }} Tk
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" :disabled="waitingForSave" class="button button-primary">
                        <template v-if="grocery_id">Save Changes</template>
                        <template v-if="!grocery_id">Save</template>
                    </button>
                    <input type="reset" @click.prevent="resetForm" v-if="!grocery_id" value="Reset" class="button button-secondary">
                </div>

            </form>

        </div>
    </div>
    <div class="col-md-7 px-3">
        <div class="bg-light px-3 py-2">

            <div class="clearfix">
                <div class="float-left">
                    <h5 class="mt-2 mb-3">Grocery Shopping List</h5>
                </div>

                <div class="float-right pt-2">

                    <select v-model="filters.member_id">
                       <option value=""> -- Select Member -- </option>
                        <?php
                            $members = $mm_db->query("SELECT * FROM mm_members WHERE deleted_at IS NULL");
                            while ($row = $members->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>{$row['name']}</option>";
                            }
                        ?>
                    </select> 

                    <select v-model="filters.year">
                        <?php 
                            for ($year = date('Y'); $year > 2020; $year--) {
                                echo "<option value='$year'>$year</option>";
                            } 
                        ?>
                    </select>

                    <select v-model="filters.month">
                        <?php
                            for ($i = 1; $i <= 12; $i++) {
                                $month = date("F", mktime(0, 0, 0, $i));
                                echo "<option value='$i'>$month</option>";
                            }
                        ?>
                    </select>

                </div>
            </div>

            <mm-data-table
                :resources="groceryShoppings"
                :headers="headers"
                class="text-center"
            >
                <template v-slot:[`sl`]="{ item, i }">
                    {{ i + 1 }}
                </template>
                <template v-slot:[`action`]="{ item }">
                    <button @click.prevent="showGrocery(item)" class="button">View</button>
                    <button @click.prevent="editGrocery(item)" class="button">Edit</button>
                    <button @click.prevent="deleteGrocery(item)" class="button">Delete</button>
                </template>
            </mm-data-table>

        </div>
    </div>

    <div v-if="showModal" :style="{display: showModal ? 'block' : none}" class="mm-modal-wrapper">
        <div class="mm-modal rounded">
            <div class="clearfix px-3 pt-2 border-bottom">
                <div class="float-left">
                    <h5 class="text-uppercase">
                        <b> Grocery shopping details </b>
                    </h5>
                </div>
                <div class="float-right">
                    <button @click.prevent="showModal = false" class="mm-modal-close">&times;</button>
                </div>
            </div>
            <div class="px-3 pt-2 pb-3">
                <div class="clearfix mb-2">
                    <div class="float-left">
                        <b>Name :</b> {{ selectedGrocery.member_name }} <br>
                        <b>Phone :</b> {{ selectedGrocery.phone }}
                    </div>
                    <div class="float-right">
                        <b>Date :</b> {{ selectedGrocery.date }} <br>
                        <b>Total Cost :</b> {{ selectedGrocery.total_amount }} Tk <br>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table text-center table-sm">
                        <thead>
                            <th>SL</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                        </thead>
                        <tbody v-for="(_item, j) in selectedGrocery.grocery_shopping_details">
                            <tr>
                                <td>{{ j + 1 }}</td>
                                <td>{{ _item.item_name }}</td>
                                <td>{{ _item.quantity }}</td>
                                <td>{{ _item.amount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    Vue.component('mm-data-table', mm_data_table);

    new Vue({
        el: "#mm-app",
        data: {
            grocery_id: null,
            selectedGrocery: Object,

            grocery: {
                date: new Date().toISOString().substr(0, 10),
                member_id: '',
                grocery_items: []
            },

            groceryItem: {
                item_name: '',
                quantity: null,
                amount: null,
            },

            filters: {
                member_id: '',
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1,
            },

            showModal: false,
            error: '',
            success: '',

            headers: [
                { text: "SL", key: "sl" },
                { text: "Date", key: "date", search: true },
                { text: "Amount", key: "total_amount", search: true },
                { text: "Action", key: "action" },
            ],
            groceryShoppings: [],
            waitingForSave: false,

            URL: "<?php echo addslashes(plugin_dir_url(__DIR__) . 'ajax/grocery.php') ?>",
        },
        watch: {
            "filters.member_id": function () {
                this.getGroceryShoppings();
            },
            "filters.year": function () {
                this.getGroceryShoppings();
            },
            "filters.month": function () {
                this.getGroceryShoppings();
            },
        },
        methods: {
            addGroceryItem() {
                if (!this.groceryItem.item_name) {
                    alert('Item name field is required');
                    return;
                }

                if (!this.groceryItem.amount) {
                    alert('Amount field is required');
                    return;
                }

                this.grocery.grocery_items.push({...this.groceryItem});
                this.groceryItem.quantity = this.groceryItem.amount = null;
                this.groceryItem.item_name = '';
            },
            removeItem(index) {
                this.grocery.grocery_items.splice(index, 1);
            },
            getGroceryShoppings() {
                axios.post(this.URL,  {action: 'get', ...this.filters})
                    .then(res => this.groceryShoppings = res.data.grocery_shoppings);
            },
            showGrocery(grocery) {
                this.selectedGrocery = grocery;
                this.showModal = true;
            },
            editGrocery(grocery) {
                this.grocery_id = grocery.id;
                this.grocery.date = grocery.date;
                this.grocery.member_id = grocery.member_id;
                this.grocery.grocery_items = [...grocery.grocery_shopping_details];
            },
            async saveGrocery() {
                if (!this.grocery.date) {
                    this.error = 'The date field is required';
                } else if (!this.grocery.member_id) {
                    this.error = 'Member selection is required';
                } else if (!this.grocery.grocery_items.length) {
                    this.error = 'Please add at least one grocery item';
                } else {
                    this.waitingForSave = true;

                    let data = {...this.grocery};
                    data.action = this.grocery_id ? 'update' : 'store';
                    if (this.grocery_id) data.grocery_id = this.grocery_id;

                    await axios.post(this.URL, data)
                        .then(res => {
                            this.success = res.data.message;
                            this.resetForm();
                            this.getGroceryShoppings();
                        }).catch(e => {
                            this.error = e.response.data.message;
                        });

                    this.waitingForSave = false;
                }
                setTimeout(() => this.error = this.success = '', 3000);
            },
            async deleteGrocery(grocery) {
                if (!confirm('Are you sure?')) return;
                
                this.waitingForDelete = true;

                await axios.post(this.URL, {grocery_id: grocery.id, action: 'delete'})
                    .then(res => {
                        this.success = res.data.message;
                        this.getGroceryShoppings();
                    }).catch(e => {
                        this.error = e.response.data.message;
                    });

                setTimeout(() => {
                    this.waitingForDelete = false;
                }, 5000);
            },
            resetForm() {
                this.grocery_id = null;
                this.grocery.date = new Date().toISOString().substr(0, 10);
                this.grocery.member_id = '';
                this.grocery.grocery_items = [];
            }
        }
    })
</script>
