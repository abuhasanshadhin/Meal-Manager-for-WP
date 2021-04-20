<?php
require '../includes/functions.php';
require '../includes/header.php';
?>

<div id="mm-app" class="row mt-2 no-gutters">
    <div class="col-md-4">
        <div class="bg-light px-3 py-2">
            <h5 class="mt-2 mb-3">
                <template v-if="room_id">Edit Room</template>
                <template v-if="!room_id">Add New Room</template>
            </h5>

            <div :style="{display: error || success ? '' : 'none'}" style="display:none">
                <div v-if="error" class="error mb-2">{{ error }}</div>
                <div v-if="success" class="updated mb-2">{{ success }}</div>
            </div>

            <form @submit.prevent="saveRoom" method="post">

                <div class="form-group">
                    <label for="name"> Room Number <span class="text-danger">*</span> </label>
                    <input type="text" v-model="room_number" id="name" class="form-control">
                </div>

                <div class="form-group">
                    <button type="submit" :disabled="waitingForSave" class="button button-primary">
                        <template v-if="room_id">Save Changes</template>
                        <template v-if="!room_id">Save</template>
                    </button>
                    <input type="reset" @click.prevent="resetForm" v-if="!room_id" value="Reset" class="button button-secondary">
                </div>

            </form>

        </div>
    </div>
    <div class="col-md-8 px-3">
        <div class="bg-light px-3 py-2">

            <h5 class="mt-2 mb-3">Rooms</h5>

            <mm-data-table
                :resources="rooms"
                :headers="headers"
                class="text-center"
            >
                <template v-slot:[`sl`]="{ item, i }">
                    {{ i + 1 }}
                </template>
                <template v-slot:[`action`]="{ item }">
                    <button @click.prevent="editRoom(item)" class="button">Edit</button>
                    <button @click.prevent="deleteRoom(item)" class="button">Delete</button>
                </template>
            </mm-data-table>

        </div>
    </div>
</div>

<script>
    Vue.component('mm-data-table', mm_data_table);

    new Vue({
        el: "#mm-app",
        data: {
            room_id: null,
            room_number: '',

            error: '',
            success: '',

            headers: [
                { text: "SL", key: "sl" },
                { text: "Room Number", key: "room_number", search: true },
                { text: "Total Member", key: "total_member", search: true },
                { text: "Action", key: "action" },
            ],
            rooms: [],
            waitingForSave: false,

            URL: "<?php echo addslashes(plugin_dir_url(__DIR__) . 'ajax/room.php') ?>",
        },
        created() {
            this.getRooms();
        },
        methods: {
            getRooms() {
                axios.post(this.URL,  {action: 'get'})
                    .then(res => this.rooms = res.data.rooms);
            },
            editRoom(room) {
                this.room_id = room.id;
                this.room_number = room.room_number;
            },
            async saveRoom() {
                if (!this.room_number) {
                    this.error = 'The room number field is required'
                } else {
                    this.waitingForSave = true;

                    let data = {room_number: this.room_number};
                    data.action = this.room_id ? 'update' : 'store';
                    if (this.room_id) data.room_id = this.room_id;

                    await axios.post(this.URL, data)
                        .then(res => {
                            this.success = res.data.message;
                            this.resetForm();
                            this.getRooms();
                        }).catch(e => {
                            this.error = e.response.data.message;
                        });

                    this.waitingForSave = false;
                }
                setTimeout(() => this.error = this.success = '', 3000);
            },
            async deleteRoom(room) {
                if (!confirm('Are you sure?')) return;

                this.waitingForDelete = true;

                await axios.post(this.URL, {room_id: room.id, action: 'delete'})
                    .then(res => {
                        this.success = res.data.message;
                        this.getRooms();
                    }).catch(e => {
                        this.error = e.response.data.message;
                    });

                setTimeout(() => {
                    this.waitingForDelete = false;
                }, 5000);
            },
            resetForm() {
                this.room_id = null;
                this.room_number = '';
            }
        }
    })
</script>
