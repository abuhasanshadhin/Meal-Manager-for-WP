const mm_data_table = Vue.component('mm-data-table', {
    template: `
    <div class="position-relative">
        <div class="row mb-2">
            <div class="col-md-4">
                <div class="d-flex">
                    <div class="mr-2">Show :</div>
                    <div class="mr-2">
                        <select
                            v-model.number="showEntries"
                            class="form-control"
                        >
                            <option
                                v-for="i in [8, 25, 50, 100, 'All']"
                                :value="i"
                                :key="i"
                            >
                                {{ i }}
                            </option>
                        </select>
                    </div>
                    <div>entries</div>
                </div>
            </div>
            <div class="col-md-4 ml-auto">
                <input
                    type="text"
                    v-model="searchQuery"
                    class="form-control"
                    placeholder="Search"
                />
            </div>
        </div>

        <div class="table-responsive">
            <table class="mm-table table table-sm table-striped">
                <thead>
                    <tr>
                        <th
                            v-for="(header, hInd) in headers"
                            :key="hInd"
                            class="text-center"
                        >
                            {{ header.text }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="loading">
                        <div class="slider">
                            <div class="line"></div>
                            <div class="sub-line inc"></div>
                            <div class="sub-line dec"></div>
                        </div>
                    </template>
                    <template v-else>
                        <template v-if="_resources.length">
                            <tr
                                v-for="(resource, ind) in _resources"
                                :key="ind"
                            >
                                <td
                                    v-for="(_header, _hInd) in headers"
                                    :key="_hInd"
                                    style="vertical-align: middle"
                                >
                                    <slot
                                        :name="_header.key"
                                        :item="resource"
                                        :i="ind"
                                        :start="startLength"
                                    >
                                        {{ resource[_header.key] }}
                                    </slot>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr>
                                <td :colspan="headers.length">
                                    <div class="text-center">
                                        No data available in this table
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="clearfix">
            <div class="float-left" v-if="_resources.length">
                Showing {{ startLength + 1 }} to
                {{ startLength + _resources.length }} of
                {{ totalLength }} entries
            </div>
            <div class="float-right">
                <nav
                    v-if="_totalPage > 1"
                    class="data-table"
                    aria-label="Page navigation example"
                >
                    <ul class="pagination">
                        <li
                            class="page-item"
                            :class="{ disabled: currentPage < 2 }"
                        >
                            <a
                                class="page-link"
                                @click="paginate(currentPage - 1)"
                                href="javascript:"
                                aria-label="Previous"
                            >
                                <span style="user-select: none">Previous</span>
                            </a>
                        </li>
                        <li
                            v-for="(page, ind) in _totalPage"
                            :key="ind"
                            :class="{ active: page == currentPage }"
                            class="page-item"
                        >
                            <a
                                href="javascript:"
                                @click="paginate(page)"
                                class="page-link"
                            >
                                {{ page }}
                            </a>
                        </li>
                        <li
                            class="page-item"
                            :class="{ disabled: currentPage > _totalPage - 1 }"
                        >
                            <a
                                class="page-link"
                                @click="paginate(currentPage + 1)"
                                href="javascript:"
                                aria-label="Next"
                            >
                                <span style="user-select: none">Next</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    `,
    props: [
        "headers", 
        "resources", 
        "loading"
    ],
    data() {
        return {
            startLength: 0,
            endLength: 0,
            showEntries: 8,
            searchQuery: "",
            totalLength: 0,
            currentPage: 1,
            searchKeys: [],
        };
    },
    watch: {
        showEntries: function (val) {
            this.startLength = 0;
            this.endLength = val == "All" ? this.resources.length : val;
            this.currentPage = 1;
        },
        searchQuery: function (val) {
            if (val) {
                this.startLength = 0;
                this.endLength = this.showEntries;
                this.currentPage = 1;
            }
        },
    },
    computed: {
        _resources() {
            this.endLength =
                this.endLength > 0 ? this.endLength : this.showEntries;
            let fr = this.filteredResources(
                this.resources,
                this.startLength,
                this.endLength,
                this.searchQuery
            );
            this.totalLength = fr._length;
            return fr._data;
        },
        _totalPage() {
            return Math.ceil(this.totalLength / this.showEntries);
        },
    },
    created() {
        this.searchKeys = this.headers.map((h) => {
            if (h.search != undefined && h.search == true) return h.key;
        });
        this.searchKeys = this.searchKeys.filter((k) => k != undefined);
        if (!this.searchKeys.length)
            this.searchKeys = this.headers.map((h) => h.key);
    },
    methods: {
        paginate(pageNumber) {
            let sE = this.showEntries;
            this.currentPage = pageNumber;
            this.startLength = sE * (pageNumber - 1);
            this.endLength = this.startLength + sE;
        },
        filteredResources(data, startLength, showEntries, searchQuery) {
            if (searchQuery) {
                let filteredData = this.searchData(data, searchQuery);
                return {
                    _data: filteredData.slice(startLength, showEntries),
                    _length: filteredData.length,
                };
            } else {
                return {
                    _data: data.slice(startLength, showEntries),
                    _length: data.length,
                };
            }
        },
        searchData(data, query) {
            let _query = query.toLowerCase();
            let searchKeys = this.searchKeys;
            return data.filter((item) => {
                for (let i = 0; i < searchKeys.length; i++) {
                    let _item = String(item[searchKeys[i]]).toLowerCase();
                    if (_item.indexOf(_query) !== -1) return true;
                }
                return false;
            });
        },
    },
})