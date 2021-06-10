<template>
    <div>
        <h1 class='py-3'>
            Match competenze
        </h1>
        <form>
             <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-free-tab" data-bs-toggle="tab" data-bs-target="#nav-free" type="button" role="tab" aria-controls="nav-free" aria-selected="true">Testo libero</button>
                    <button class="nav-link" id="nav-choice-tab" data-bs-toggle="tab" data-bs-target="#nav-choice" type="button" role="tab" aria-controls="nav-choice" aria-selected="false">Testo a scelta</button>
                    <button class="nav-link" id="nav-random-tab" data-bs-toggle="tab" data-bs-target="#nav-random" type="button" role="tab" aria-controls="nav-random" aria-selected="false">Testo random</button>
                </div>
            </nav>
            <div class="tab-content border-start border-end border-bottom rounded-bottom" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-free" role="tabpanel" aria-labelledby="nav-free-tab">
                    <div class="p-3">
                        <label for="exampleFormControlTextarea1" class="form-label">Inserire il testo da confrontare</label>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" v-model="text"></textarea>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-choice" role="tabpanel" aria-labelledby="nav-choice-tab">
                    <div class="p-3">
                        <div class="pb-3">
                            <label for="select-sentence" class="form-label">Scegliere il testo da confrontare</label>
                            <select @change="selectSentence" class="form-select" id="select-sentence" v-model="chosen_sentence">
                                <option value="-1" selected>[Seleziona una frase]</option>
                                <option :value="index" v-for="(sentence, index) in sentences">
                                    #{{ sentence.id }} -
                                    {{ sentence.sentence | limit(100) }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <textarea class="form-control" rows="3" v-model="text_choice" disabled></textarea>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-random" role="tabpanel" aria-labelledby="nav-random-tab">
                    <div class="p-3">
                        <div class="pb-3">
                            <div class="clearfix">
                                <button @click.prevent="newExtraction" class="ms-3 btn btn-dark float-end btn-sm">
                                    <i class="fas fa-dice"></i> Nuova estrazione
                                </button>
                                <p class="form-label">
                                    Frase random: #{{ random_sentence }}
                                </p>
                            </div>
                            <div class="pt-1">
                                <textarea class="form-control" rows="3" v-model="text_random" disabled></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-cols-md-auto g-3 align-items-center mt-1">
                <div class="col-12">
                    Più specifico
                </div>
                <div class="col-12">
                    <input type="range" class="form-range" min="-2" max="2" step="1" v-model="run_type">
                </div>
                <div class="col-12">
                    Più generico
                </div>

                <div class="col-12 ms-md-5">
                    Risultati:
                </div>
                <div class="col-12 col-xs-form-label">
                    <input type="number" min="1" max="50" step="1" v-model="numResults" class="form-control">
                </div>

                <div class="col-12" style="margin-left: auto;">
                    <i v-show="loading_query" class="fas fa-circle-notch fa-spin"></i>
                    <button class="btn btn-primary" @click.prevent="go" :disabled="submitDisabled">Invia</button>
                </div>

            </div>
        </form>
        <div v-if="results.length">
            <h2 class="py-4" id="results">
                Risultati
            </h2>

            <div class="row">
                <div class="col-lg-6 col-12">
                    <h3 class="py-1">TF-IDF</h3>
                    <ul class="list-group pb-3">
                        <li :class="{ 'list-group-item-danger' : result.no, 'list-group-item-success' : result.yes }" class="list-group-item d-flex justify-content-between align-items-start" v-for="(result, index) in results[0]">
                            <div class="pe-1">
                                {{ result.text }}
                            </div>
                            <div class="text-nowrap">
                                <span class="badge bg-primary rounded-pill">{{ result.sim | round(2) }}</span>
                                <a href="#" @click.prevent="setYes(0, index)" class="badge bg-success rounded-pill"><i class="far fa-smile"></i></a>
                                <a href="#" @click.prevent="setNo(0, index)" class="badge bg-danger rounded-pill"><i class="far fa-frown"></i></a>
                                <a target="_blank" :href="result.key" class="badge bg-secondary rounded-pill">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6 col-12">
                    <h3 class="py-1">Sentence vectors</h3>
                    <ul class="list-group pb-3">
                        <li :class="{ 'list-group-item-danger' : result.no, 'list-group-item-success' : result.yes }" class="list-group-item d-flex justify-content-between align-items-start" v-for="(result, index) in results[1]">
                            <div class="pe-1">
                                {{ result.text }}
                            </div>
                            <div class="text-nowrap">
                                <span class="badge bg-primary rounded-pill">{{ result.sim | round(2) }}</span>
                                <a href="#" @click.prevent="setYes(1, index)" class="badge bg-success rounded-pill"><i class="far fa-smile"></i></a>
                                <a href="#" @click.prevent="setNo(1, index)" class="badge bg-danger rounded-pill"><i class="far fa-frown"></i></a>
                                <a target="_blank" :href="result.key" class="badge bg-secondary rounded-pill">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                // "mult": [1.0, 0.9, 0.8],                
                "run_type": 0,

                "text": "",
                "text_choice": "",
                "text_random": "",

                "loading_query": false,
                "submitDisabled": false,

                "results": [],
                "numResults": 10,

                "session_id": 0,

                "selected_tab": "",
                "sentences": [],

                "chosen_sentence": -1,
                "random_sentence": -1
            };
        },
        mounted: function() {
            var oldThis = this;
            var tabElements = document.querySelectorAll('button[data-bs-toggle="tab"]');
            for (var i = 0; i < tabElements.length; i++) {
                tabElements[i].addEventListener('show.bs.tab', function (event) {
                    oldThis.selected_tab = event.target.id;
                })
            }

            this.axiosApi.get("?action=listSentences", {})
            .then(function (data) {
                oldThis.sentences = data.data.sentences;
                oldThis.newExtraction();
            })

            // Just to populate selected_tab
            var firstTabEl = document.querySelector('button[data-bs-toggle="tab"]');
            var firstTab = new bootstrap.Tab(firstTabEl)
            firstTab.show();
            this.selected_tab = firstTabEl.id;
        },
        props: ["axiosApi"],
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
            }
        },
        watch: {
            loading_query: function(newValue) {
                if (newValue) {
                    this.submitDisabled = true;
                    this.results = [];
                }
                else {
                    this.submitDisabled = false;
                }
            }
        },
        methods: {
            newExtraction: function() {
                this.random_sentence = Math.floor(Math.random() * this.sentences.length);
                this.text_random = this.sentences[this.random_sentence].sentence;
            },
            selectSentence: function() {
                if (this.chosen_sentence > -1) {
                    this.text_choice = this.sentences[this.chosen_sentence].sentence;
                }
                else {
                    this.text_choice = "";
                }
            },
            setYes: function(main_index, index) {
                var oldThis = this;
                this.results[main_index][index]['yes'] = ! this.results[main_index][index]['yes'];
                this.results[main_index][index]['no'] = false;
                this.$set(this.results, main_index, this.results[main_index]);
                this.saveSession()
                .then(function (data) {
                    oldThis.session_id = data.data.session_id;
                })
            },
            setNo: function(main_index, index) {
                var oldThis = this;
                this.results[main_index][index]['no'] = ! this.results[main_index][index]['no'];
                this.results[main_index][index]['yes'] = false;
                this.$set(this.results, main_index, this.results[main_index]);
                this.saveSession()
                .then(function (data) {
                    oldThis.session_id = data.data.session_id;
                })
            },
            saveSession: function() {
                var oldThis = this;
                var pars = {
                    session_id: this.session_id,
                    value: this.results
                };
                return this.axiosApi.post("?action=setSession", pars);
            },
            go: function() {

                var t = this.text;
                var id = 0;
                switch (this.selected_tab) {
                    case "nav-choice-tab":
                        t = this.text_choice;
                        if (this.chosen_sentence != -1) {
                            id = this.sentences[this.chosen_sentence].id;
                        }
                        break;
                    case "nav-random-tab":
                        t = this.text_random;
                        if (this.random_sentence != -1) {
                            id = this.sentences[this.random_sentence].id;
                        }
                        break;
                }

                t = t.trim();

                if (t.length == 0) {
                    alert("Nessuna frase inserita");
                    return;
                }

                if (t.length < 10) {
                    alert("Frase troppo corta (min 10 caratteri)");
                    return;
                }

                this.loading_query = true;

                var oldThis = this;
                var pars = {
                    "run_type": this.run_type,
                    "text": t,
                    "numResults": this.numResults,
                    "tab": this.selected_tab,
                    "sentence_id": id
                };
                this.axiosApi.post("https://dh-server.fbk.eu/cds-api/", pars)
                .then(function (data) {
                    var results_tfidf = data.data.values_tfidf.slice(0, oldThis.numResults);
                    var results_sent = data.data.values_sent.slice(0, oldThis.numResults);

                    pars['mult'] = data.data.multipliers;

                    for (var i = 0; i < results_tfidf.length; i++) {
                        var o = results_tfidf[i];
                        o['yes'] = false;
                        o['no'] = false;
                        results_tfidf[i] = o;
                    }
                    for (var i = 0; i < results_sent.length; i++) {
                        var o = results_sent[i];
                        o['yes'] = false;
                        o['no'] = false;
                        results_sent[i] = o;
                    }
                    oldThis.results[0] = results_tfidf;
                    oldThis.results[1] = results_sent;
                    oldThis.results[2] = pars;

                    oldThis.saveSession()
                    .then(function (data) {
                        oldThis.session_id = data.data.session_id;
                    })

                    oldThis.$nextTick(() => {
                        setTimeout(function() {
                            var elmnt = document.getElementById("results");
                            elmnt.scrollIntoView();
                        }, 0);
                    });
                })
                .finally(function () {
                    oldThis.loading_query = false;
                });
            }
        }
    }
</script>

<style>
</style>
