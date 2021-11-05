<template>
    <div class="mb-3">
        <h1 class='py-5'>
            Annotazioni
        </h1>

        <div class="card border-secondary mb-3" v-show="loggedInfo.show_help == '1'">
            <div class="card-header">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="card-body">
                <div class="card-text" style="font-size: .8em;">
                    <p>
                        Tramite questa schermata è possibile rivedere ed eventualmente annullare le annotazioni
                        realizzate in precedenza.
                        Per eliminare un'annotazione è sufficiente cliccare sul tasto
                        <span class="badge bg-danger"><i class="fas fa-trash-alt"></i></span>.
                    </p>
                </div>
            </div>
        </div>

        <div v-if="results.length > 0">

            <nav v-if="num_pages > 1">
                <ul class="pagination">
                    <li v-if="this_page > 0" class="page-item">
                        <a class="page-link" href="#" @click.prevent="goToPage(this_page - 1)">&laquo;</a>
                    </li>
                    <li v-for="n in num_pages" class="page-item" :class="{ 'active': n - 1 == this_page }">
                        <span v-if="n - 1 == this_page" class="page-link">{{ n }}</span>
                        <a v-else class="page-link" href="#" @click.prevent="goToPage(n - 1)">{{ n }}</a>
                    </li>
                    <li v-if="this_page < num_pages - 1" class="page-item">
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
                            <div class="card text-dark bg-light mb-3">
                                <div class="card-header">Frase analizzata</div>
                                <div class="card-body">
                                    <h5 class="card-title">{{ result.value[2].text }}</h5>
                                    <!-- <p class="card-text">{{ result.value[2].tokens }}</p> -->
                                </div>
                            </div>
                            <p>
                                <!-- Configurazione: -->
                                <!-- <span class="badge bg-primary rounded-pill ms-1">{{ conf_texts[result.value[2].run_type] }}</span> -->
                                <span>Data/ora di creazione: <strong>{{ result.created_time }}</strong></span>
                                <button class="btn btn-danger float-end" @click="deleteAnnotation(result.session_id)"><i class="fas fa-trash-alt"></i></button>
                            </p>
                            <ul class="list-group" v-if="result.value[2].count > 0">
                                <li class="list-group-item list-group-item-success" v-for="(o, index) in result.value[2].yes_results">
                                    <!-- <span class="badge bg-primary rounded-pill me-1">{{ result.value[2].yes_types[index] | yesno_type }}</span> -->
                                    {{ o.text }}
                                </li>
                                <li class="list-group-item list-group-item-danger" v-for="(o, index) in result.value[2].no_results">
                                    <!-- <span class="badge bg-primary rounded-pill me-1">{{ result.value[2].no_types[index] | yesno_type }}</span> -->
                                    {{ o.text }}
                                </li>
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
            yesno_type: function(value) {
                if (value == "0") {
                    return "TF-IDF";
                }
                else {
                    return "Sentence vectors"
                }
            },
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
        props: ["axiosApi", "loggedInfo"],
        methods: {
            deleteAnnotation: function(session_id) {
                if (!confirm("Sei sicuro di voler eliminare questa annotazione? L'operazione è irreversibile.")) {
                    return;
                }
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
                    oldThis.axiosApi.get("?action=getLoginInfo", {});
                });
            },
            goToPage: function(pageNum) {
                var oldThis = this;
                this.axiosApi.get("?action=getAnnotations&page=" + pageNum)
                .then(function(data) {
                    for (var i = 0; i < data.data.sessions.length; i++) {
                        var count = 0;
                        var yes = [];
                        var no = [];
                        var yes_types = [];
                        var no_types = [];
                        var value = data.data.sessions[i].value;
                        for (var k = 0; k < 2; k++) {
                            for (var j = 0; j < value[k].length; j++) {
                                if (value[k][j].yes || value[k][j].no) {
                                    count++;
                                    if (value[k][j].yes) {
                                        yes.push(value[k][j])
                                        yes_types.push(k);
                                    }
                                    if (value[k][j].no) {
                                        no.push(value[k][j])
                                        no_types.push(k);
                                    }
                                }
                            }
                        }
                        value[2]['count'] = count;
                        value[2]['yes_results'] = yes;
                        value[2]['no_results'] = no;
                        value[2]['yes_types'] = yes_types;
                        value[2]['no_types'] = no_types;
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
