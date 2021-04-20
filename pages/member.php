<?php
require '../includes/functions.php';
require '../includes/header.php';
?>

<div id="mm-app" class="row mt-2 no-gutters">
    <div class="col-md-4">
        <div class="bg-light px-3 py-2">
            <h5 class="mt-2 mb-3">
                <template v-if="member_id">Edit Member</template>
                <template v-if="!member_id">Add New Member</template>
            </h5>

            <div :style="{display: error || success ? '' : 'none'}" style="display:none">
                <div v-if="error" class="error mb-2">{{ error }}</div>
                <div v-if="success" class="updated mb-2">{{ success }}</div>
            </div>

            <form @submit.prevent="saveMember" method="post">

                <div class="form-group">
                    <label for="room"> Room Number <span class="text-danger">*</span> </label>
                    <select v-model="member.room_id" id="room" class="form-control">
                        <option value=""> -- Select Room Number -- </option>
                        <?php
                            $rooms = $mm_db->query("SELECT * FROM mm_rooms WHERE deleted_at IS NULL");
                            while ($row = $rooms->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>{$row['room_number']}</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="name"> Name <span class="text-danger">*</span> </label>
                    <input type="text" v-model="member.name" id="name" class="form-control">
                </div>

                <div class="form-group">
                    <label for="phone"> Phone <span class="text-danger">*</span> </label>
                    <input type="text" v-model="member.phone" id="phone" class="form-control">
                </div>

                <div class="form-group">
                    <label for="address"> Address </label>
                    <textarea v-model="member.address" id="address" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" :disabled="waitingForSave" class="button button-primary">
                        <template v-if="member_id">Save Changes</template>
                        <template v-if="!member_id">Save</template>
                    </button>
                    <input type="reset" @click.prevent="resetForm" v-if="!member_id" value="Reset" class="button button-secondary">
                </div>

            </form>

        </div>
    </div>
    <div class="col-md-8 px-3">
        <div class="bg-light px-3 py-2">

            <h5 class="mt-2 mb-3">Members</h5>

            <mm-data-table
                :resources="members"
                :headers="headers"
                class="text-center"
            >
                <template v-slot:[`sl`]="{ item, i }">
                    {{ i + 1 }}
                </template>
                <template v-slot:[`action`]="{ item }">
                    <button @click.prevent="editMember(item)" class="button">Edit</button>
                    <button @click.prevent="deleteMember(item)" class="button">Delete</button>
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
            member_id: null,
            member: {
                room_id: '',
                name: '',
                phone: '',
                address: ''
            },

            error: '',
            success: '',

            headers: [
                { text: "SL", key: "sl" },
                { text: "Name", key: "name", search: true },
                { text: "Phone", key: "phone", search: true },
                { text: "Address", key: "address", search: true },
                { text: "Room No.", key: "room_number", search: true },
                { text: "Action", key: "action" },
            ],
            members: [],
            waitingForSave: false,

            URL: "<?php echo addslashes(plugin_dir_url(__DIR__) . 'ajax/member.php') ?>",
        },
        created() {
            this.getMembers();
        },
        methods: {
            getMembers() {
                axios.post(this.URL,  {action: 'get'})
                    .then(res => this.members = res.data.members);
            },
            editMember(member) {
                this.member_id = member.id;
                Object.keys(this.member).forEach(k => this.member[k] = member[k]);
            },
            async saveMember() {
                if (!this.member.room_id) {
                    this.error = 'The room number field is required'
                } else if (!this.member.name) {
                    this.error = 'The name field is required'
                } else if (!this.member.phone) {
                    this.error = 'The phone field is required'
                } else {
                    this.waitingForSave = true;

                    let data = {...this.member};
                    data.action = this.member_id ? 'update' : 'store';
                    if (this.member_id) data.member_id = this.member_id;

                    await axios.post(this.URL, data)
                        .then(res => {
                            this.success = res.data.message;
                            this.resetForm();
                            this.getMembers();
                        }).catch(e => {
                            this.error = e.response.data.message;
                        });

                    this.waitingForSave = false;
                }
                setTimeout(() => this.error = this.success = '', 3000);
            },
            async deleteMember(member) {
                if (!confirm('Are you sure?')) return;
                
                this.waitingForDelete = true;

                await axios.post(this.URL, {member_id: member.id, action: 'delete'})
                    .then(res => {
                        this.success = res.data.message;
                        this.getMembers();
                    }).catch(e => {
                        this.error = e.response.data.message;
                    });

                setTimeout(() => {
                    this.waitingForDelete = false;
                }, 5000);
            },
            resetForm() {
                this.member_id = null;
                Object.keys(this.member).forEach(key => this.member[key] = '')
            }
        }
    })
</script>
