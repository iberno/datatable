<template>
    <div class="card card-default">
        <div class="card-header">{{ response.table }}</div>

        <div class="card-body">
            <div class="row">
                <div class="form-group col-md-10">
                    <label for="filter">Perquisa Rápida!</label>
                    <input type="text" id="filter" class="form-control" v-model="quickSearchQuery">
                </div>
                <div class="form-group col-md-2">
                    <label for="limit">Limite de Dados</label>
                    <select id="limit" class="form-control" v-model="limit" @change="getRecords()">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                        <option value="">All</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th v-for="column in response.displayable">
                                <span class="sortable" @click="sortBy(column)">{{ column }}</span>
                                <div 
                                    class="arrow" 
                                    v-if="sort.key === column"
                                    :class="{ 'arrow--asc': sort.order === 'asc',
                                            'arrow--desc': sort.order === 'desc' }"
                                ></div>
                            </th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="record in filteredRecords">
                            <td v-for="columnValue, column in record">
                                <template v-if="editing.id === record.id && isUpdatable(column)">
                                        <input type="text" class="form-control" :class="{ 'is-invalid': editing.errors[column] }" :name="columnValue" v-model="editing.form[column]">
                                        <span class="invalid-feedback" role="alert" v-if="editing.errors[column]">
                                            <strong>{{ editing.errors[column][0] }}</strong>
                                        </span>
                                </template>
                                <template v-else>
                                    {{ columnValue }}
                                </template>
                            </td>
                            <td>
                                <button @click.prevent="edit(record)" class="btn btn-sm btn-link" v-if="editing.id !== record.id">
                                    Edit
                                </button>
                                <template v-if="editing.id === record.id">
                                    <button @click.prevent="update" class="btn btn-sm btn-link">
                                        Save
                                    </button>
                                    <br>
                                    <button @click.prevent="editing.id = null" class="btn btn-sm btn-link" value="Cancel">
                                        Cancel
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
    import queryString from 'query-string'
    export default {
        props: ['endpoint'],
        data () {
            return {
                response: {
                    table: '',
                    displayable: [],
                    records: []
                },
                sort: {
                    key: 'id',
                    order: 'asc',
                },
                limit: 50,
                quickSearchQuery: '',
                editing: {
                    id: null,
                    form: {},
                    errors: []
                }
            }
        },
        computed: {
            filteredRecords () {
                let data = this.response.records

                data = data.filter((row) => {
                    return Object.keys(row).some((key) => {
                        return String(row[key]).toLowerCase().indexOf(this.quickSearchQuery.toLowerCase()) > -1
                    })
                })

                if (this.sort.key) {
                    data = _.orderBy(data, (i) => {
                        let value = i[this.sort.key]

                        if (!isNaN(parseFloat(value))) {
                            return parseFloat(value)
                        }

                        return String(i[this.sort.key]).toLowerCase()
                    }, this.sort.order)
                }

                return data
            }
        },
        mounted() {
            this.getRecords()
        },
        methods: {
            getRecords () {
                return axios.get(`${this.endpoint}?${this.getQueryParameters()}`).then((response) => {
                    this.response = response.data.data
                })
            },
            getQueryParameters () {
                return queryString.stringify({
                    limit: this.limit
                })
            },
            sortBy(column) {
                this.sort.key = column
                this.sort.order = this.sort.order === 'asc' ? 'desc' : 'asc'
            },
            edit (record) {
                this.editing.errors = []
                this.editing.id = record.id
                this.editing.form = _.pick(record, this.response.updatable)
            },
            isUpdatable (column) {
                return this.response.updatable.includes(column)
            },
            update () {
                axios.patch(`${this.endpoint}/${this.editing.id}`, this.editing.form).then(() => {
                    this.getRecords().then(() => {
                        this.editing.id = null
                        this.editing.form = {}
                    })
                }).catch ((error) => {
                    this.editing.errors = error.response.data.errors
                })
            }
        }
    }
</script>

<style lang="scss">
.sortable {
    cursor: pointer;
}
.arrow {
    display: inline-block;
    vertical-align: middle;
    width: 0;
    height: 0;
    margin-left: 5px;
    opacity: .6;

    &--asc {
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-bottom: 4px solid #222;
    }
    &--desc {
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 4px solid #222;
    }
}
</style>

