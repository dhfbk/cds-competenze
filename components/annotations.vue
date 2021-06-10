<template>
    <div class="mb-3">
        <h1 class='py-5'>
            Annotazioni
        </h1>

        <div v-if="results.length > 0">

            <nav aria-label="Page navigation example" v-if="num_pages > 1">
                <ul class="pagination">
                    <li v-if="this_page > 0">
                        <a class="page-link" href="#" @click.prevent="goToPage(this_page - 1)">&laquo;</a>
                    </li>
                    <li v-for="n in num_pages" class="page-item" :class="{ 'active': n - 1 == this_page }">
                        <span v-if="n - 1 == this_page" class="page-link">{{ n }}</span>
                        <a v-else class="page-link" href="#" @click.prevent="goToPage(n - 1)">{{ n }}</a>
                    </li>
                    <li v-if="this_page < num_pages - 1">
                        <a class="page-link" href="#" @click.prevent="goToPage(this_page + 1)">&raquo;</a>
                    </li>
                </ul>
            </nav>

            <div class="accordion" id="listSessions">
                <div v-for="result in results" class="accordion-item">
                    <h2 class="accordion-header" :id="'sessionHeader' + result.session_id">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            :data-bs-target="'#sessionCollapse' + result.session_id"
                            aria-expanded="true"
                            :aria-controls="'sessionCollapse' + result.session_id">
                            <span class="badge bg-primary rounded-pill me-3">{{ result.value[2].tab | goodName }}</span>
                            {{ result.value[2].text | limit(100) }}
                        </button>
                    </h2>
                    <div class="accordion-collapse collapse"
                        :id="'sessionCollapse' + result.session_id"
                        :aria-labelledby="'sessionHeader' + result.session_id"
                        data-bs-parent="#listSessions">
                        <div class="accordion-body">
                            <p>
                                <!-- Moltiplicatori: -->
                                <!-- <span class="badge bg-primary rounded-pill ms-3" v-for="mult in result.value[2].mult">{{ mult | round(2) }}</span> -->
                                Configurazione:
                                <span class="badge bg-primary rounded-pill ms-3">{{ conf_texts[result.value[2].run_type] }}</span>
                                <button class="btn btn-danger float-end" @click="deleteAnnotation(result.session_id)"><i class="fas fa-trash-alt"></i></button>
                            </p>
                            <ul class="list-group" v-if="result.value[2].count > 0">
                                <li class="list-group-item list-group-item-success" v-for="o in result.value[2].yes_results">{{ o.text }}</li>
                                <li class="list-group-item list-group-item-danger" v-for="o in result.value[2].no_results">{{ o.text }}</li>
                            </ul>
                            <div v-else>
                                Non ci sono annotazioni.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else>
            <p>Non ci sono annotazioni</p>
        </div>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                results: [],
                num_pages: 0,
                this_page: 0,
                conf_texts: {
                    "-2": "Molto specifico",
                    "-1": "Specifico",
                    "0": "Media",
                    "1": "Generico",
                    "2": "Molto generico"
                }
            };
        },
        filters: {
            limit: function(value, limit) {
                if (value.length > limit) {
                    value = value.substring(0, limit) + " [...]";
                }
                return value;
            },
            round: function(value, decimals) {
                if (!value) {
                    value = 0;
                }

                if (!decimals) {
                    decimals = 0;
                }

                value = Math.round(value * Math.pow(10, decimals)) / Math.pow(10, decimals);
                return value.toFixed(decimals);
            },
            goodName: function(value) {
                switch (value) {
                    case "nav-choice-tab":
                        return "Testo a scelta";
                    case "nav-random-tab":
                        return "Testo random";
                    case "nav-free-tab":
                        return "Testo libero";
                }
                return "Testo libero";
            }
        },
        mounted: function() {
            this.goToPage(0);
        },
        props: ["axiosApi"],
        methods: {
            deleteAnnotation: function(session_id) {
                var oldThis = this;
                this.axiosApi.post("?action=deleteSession", {
                    session_id: session_id
                })
                .then(function(data) {
                    if (oldThis.results.length == 1 && oldThis.this_page > 0) {
                        oldThis.goToPage(oldThis.this_page - 1);
                    }
                    else {
                        oldThis.goToPage(oldThis.this_page);
                    }
                });
            },
            goToPage: function(pageNum) {
                var oldThis = this;
                this.axiosApi.get("?action=getAnnotations&page=" + pageNum)
                .then(function(data) {
                    console.log(data);
                    for (var i = 0; i < data.data.sessions.length; i++) {
                        var count = 0;
                        var yes = [];
                        var no = [];
                        var value = data.data.sessions[i].value;
                        for (var k = 0; k < 2; k++) {
                            for (var j = 0; j < value[k].length; j++) {
                                if (value[k][j].yes || value[k][j].no) {
                                    count++;
                                    if (value[k][j].yes) {
                                        yes.push(value[k][j])
                                    }
                                    if (value[k][j].no) {
                                        no.push(value[k][j])
                                    }
                                }
                            }
                        }
                        value[2]['count'] = count;
                        value[2]['yes_results'] = yes;
                        value[2]['no_results'] = no;
                    }
                    oldThis.num_pages = data.data.pages;
                    oldThis.results = data.data.sessions;
                    oldThis.this_page = pageNum;
                });
            }
        }
    }
</script>

<style>
</style>
