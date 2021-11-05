<template>
    <div>
        <h1 class='py-3'>
            Verifica associazioni competenze
        </h1>

        <div v-show="loggedInfo === undefined || loggedInfo.topic == 0" class="alert alert-warning mt-3">
            Non hai ancora inserito la tua disciplina. <router-link class="alert-link" href="#" to="/options">Clicca qui per farlo</router-link>
        </div>
        <p>
            Questa applicazione permette di confrontare una frase descrivente una competenza con le descrizioni previste nella classificazione
            europea <a href='https://ec.europa.eu/esco/portal' target='_blank'>ESCO</a> delle competenze.
        </p>
        <p>
            Grazie per aver dato la tua disponibilità al nostro studio. Con questa applicazione ci aiuterai ad addestrare un sistema di Intelligenza Artificiale ad associare una frase in linguaggio naturale a una specifica competenza della classificazione europea ESCO. Per un buon funzionamento il sistema ha bisogno di apprendere le corrette associazioni semantiche tra frasi di uso comune e le competenze contenute in ESCO. Quante più frasi riuscirai a valutare, tanto più significativo sarà il tuo contributo.
        </p>

        <div class="card border-secondary mb-3" v-show="loggedInfo.show_help == '1' && show_help_done == '1'">
            <div class="card-header">
                Come funziona?
            </div>
            <div class="card-body">
                <div class="card-text" style="font-size: .8em;">
                    <p>
                        Inserisci una frase che descrive una competenza (es. da acquisire in un’esperienza di Alternanza Scuola-Lavoro o maturata al termine
                        dell’esperienza fatta) e il sistema, utilizzando due differenti algoritmi informatici, estrae una lista di possibili competenze dalla
                        classificazione <em>ESCO</em> associabili alla competenza inserita. Il compito a te richiesto è di giudicare i risultati dei due algoritmi valutando
                        per ogni risposta fornita se questa ha una qualche corrispondenza o attinenza con la frase di partenza.
                    </p>
                    <p>
                        Nel campo “Note” della pagina <router-link to="/options"><strong>Opzioni</strong></router-link> puoi lasciare commenti, suggerimenti o
                        qualsiasi considerazione in merito al sistema automatico di analisi delle competenze o al compito di valutazione.
                    </p>
                    <p>
                        Per inserire la frase di partenza puoi utilizzare queste due modalità:
                    </p>
                    <ul>
                        <li>
                            <strong>testo a scelta</strong>, ti permette di selezionare una frase già pre-impostata;
                        </li>
                        <li>
                            <strong>testo libero</strong>, ti permette di scrivere una frase descrivente una competenza, usando parole tue (cercando di evitare frasi troppo lunghe o contenenti più di una competenza).
                        </li>
                    </ul>
                    <p>
                        Una volta inserita la frase, clicca su <strong>Invia</strong> per visualizzare le competenze estratte.
                    </p>
                </div>
            </div>
        </div>

        <div class="alert alert-success" v-show="show_success">
            Contributo ricevuto correttamente, grazie! Se vuoi continuare, inserisci un'altra frase!
        </div>

        <form>
             <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link" id="nav-choice-tab" data-bs-toggle="tab" data-bs-target="#nav-choice" type="button" role="tab" aria-controls="nav-choice" aria-selected="false">Testo a scelta</button>
                    <button class="nav-link active" id="nav-free-tab" data-bs-toggle="tab" data-bs-target="#nav-free" type="button" role="tab" aria-controls="nav-free" aria-selected="true">Testo libero</button>
                    <!-- <button class="nav-link" id="nav-random-tab" data-bs-toggle="tab" data-bs-target="#nav-random" type="button" role="tab" aria-controls="nav-random" aria-selected="false">Testo random</button> -->
                </div>
            </nav>
            <div class="tab-content border-start border-end border-bottom rounded-bottom" id="nav-tabContent">
                <div class="tab-pane fade" id="nav-choice" role="tabpanel" aria-labelledby="nav-choice-tab">
                    <div class="p-3">
                        <div class="pb-3">
                            <label for="select-sentence" class="form-label">Scegliere il testo da confrontare:</label>
                            <select @change="selectSentence" class="form-select" id="select-sentence" v-model="chosen_sentence">
                                <option value="-1" selected>[Seleziona una frase]</option>
                                <template v-for="(sentence, index) in shuffle(sentences)">
                                    <option v-if="sentence.done == 0" :value="sentence.id">
                                    {{ sentence.sentence | limit(100) }}
                                    </option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <textarea class="form-control" rows="3" v-model="text_choice" disabled></textarea>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade show active" id="nav-free" role="tabpanel" aria-labelledby="nav-free-tab">
                    <div class="p-3">
                        <label for="exampleFormControlTextarea1" class="form-label">Inserire il testo da confrontare:</label>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" v-model="text"></textarea>
                        <div class="mt-3 alert" :class="{'alert-success' : leftChars > 20, 'alert-warning': leftChars <= 20 && leftChars > 0, 'alert-danger': leftChars == 0}">
                            Caratteri rimanenti: {{ leftChars }}
                        </div>
                    </div>
                </div>
<!--                <div class="tab-pane fade" id="nav-random" role="tabpanel" aria-labelledby="nav-random-tab">
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
                </div>-->
            </div>

            <div class="row row-cols-md-auto g-3 align-items-center mt-1">
<!--                <div class="col-12">
                    Competenze più specifiche
                </div>
                <div class="col-12">
                    <input type="range" class="form-range" min="-2" max="2" step="1" v-model="run_type">
                </div>
                <div class="col-12">
                    Competenze più generiche
                </div>

                <div class="col-12 ms-md-5">
                    Risultati:
                </div>
                <div class="col-12 col-xs-form-label">
                    <input type="number" min="1" max="50" step="1" v-model="numResults" class="form-control">
                </div>-->

                <div class="col-12" style="margin-left: auto;">
                    <i v-show="loading_query" class="fas fa-circle-notch fa-spin"></i>
                    <button class="btn btn-primary" @click.prevent="go" :disabled="submitDisabled">Invia</button>
                </div>

            </div>
        </form>
        <div v-if="results.length" class="mb-3">
            <h2 class="py-4" id="results">
                Risultati
            </h2>

            <div class="card border-secondary mb-3" v-show="loggedInfo.show_help == '1' && show_help_done == '1'">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="card-body">
                    <div class="card-text" style="font-size: .8em;">
                        <p>
                            Di seguito ti verranno mostrate le competenze della classificazione europea ESCO associate alla frase inserita usando diversi algoritmi.
                        </p>
                        <p>
                            Quello che devi fare è, per ogni competenza, cliccare su:
                        </p>
                        <ul>
                            <li>
                                <span class="badge bg-success rounded-pill"><i class="far fa-smile"></i></span>
                                se la competenza estratta è in qualche misura attinente alla frase inserita;
                            </li>
                            <li>
                                <span class="badge bg-warning rounded-pill"><i class="far fa-meh"></i></span>
                                se non riesci a stabilire l'attinenza della frase con la competenza estratta;
                            </li>
                            <li>
                                <span class="badge bg-danger rounded-pill"><i class="far fa-frown"></i></span>
                                se, al contrario, la competenza NON è per nulla o poco attinente alla frase scelta;
                            </li>
                            <li>
                                <span class="badge bg-secondary rounded-pill"><i class="fas fa-external-link-alt"></i></span>
                                se vuoi consultare la pagina di ESCO con la descrizione dettagliata della competenza in questione prima di esprimere il tuo giudizio;
                            </li>
                            <li>
                                se in dubbio sulla possibile corrispondenza o meno, è possibile non esprimere alcun parere e passa al risultato successivo.
                        </ul>
                        <p>
                            Se un risultato compare più volte (può capitare per via dei processi di analisi diversi), il sistema assegna in automatico la valutazione
                            data alla prima occorrenza a tutte le occorenze.
                            Finito di analizzare tutte le competenze associate ad una frase, potrai tornare alla sezione di ‘inserimento frase’ per proseguire con
                            le frasi successive.
                        </p>
                        <p>
                            Ti chiediamo cortesemente di valutare <strong>almeno 15 delle 30 frasi pre-impostate</strong> e inserire <strong>almeno 5 frasi a testo libero</strong>.
                        </p>
                        <p>
                            Se pensi di aver fatto un errore di annotazione in una frase già confermata, puoi eliminarla tramite il menu
                            <router-link to="/annotations"><strong>Annotazioni</strong></router-link>.
                        </p>
                        <p>
                            Tieni presente che non ci sono risposte corrette o sbagliate: valuta le risposte del sistema liberamente secondo il tuo punto di vista. Per
                            noi ciò che conta è poter raccogliere le tue opinioni.
                        </p>
                        <p>
                            Una volta completata la valutazione della frase, clicca su <strong>Conferma</strong> per inviare i risultati.
                        </p>
                        <p>
                            Grazie per la preziosa collaborazione!
                        </p>
                    </div>
                </div>
            </div>

            <div class="card text-dark bg-light mb-3">
                <div class="card-header">Frase analizzata</div>
                <div class="card-body">
                    <h5 class="card-title">{{ results[2].text }}</h5>
                    <!-- <p class="card-text">{{ results[2].tokens }}</p> -->
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 col-12">
                    <h3 class="py-2">Primo set di risultati</h3>
                    <ul class="list-group pb-3">
                        <li :class="{ 'list-group-item-danger' : result.no, 'list-group-item-success' : result.yes, 'list-group-item-warning' : !result.no && !result.yes }" class="list-group-item d-flex justify-content-between align-items-start" v-for="(result, index) in results[0]">
                            <div class="pe-1">
                                {{ result.text }}<br />
                                <!-- <small class="text-secondary">{{ result.words }}</small> -->
                            </div>
                            <div class="text-nowrap">
                                <!-- <span class="badge bg-primary rounded-pill">{{ result.sim | round(2) }}</span> -->
                                <a href="#" @click.prevent="setYes(0, index)" class="badge bg-success rounded-pill"><i class="far fa-smile"></i></a>
                                <a href="#" @click.prevent="setUnknown(0, index)" class="badge bg-warning rounded-pill"><i class="far fa-meh"></i></a>
                                <a href="#" @click.prevent="setNo(0, index)" class="badge bg-danger rounded-pill"><i class="far fa-frown"></i></a>
                                <a target="_blank" :href="result.key" class="badge bg-secondary rounded-pill">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6 col-12">
                    <h3 class="py-2">Secondo set di risultati</h3>
                    <ul class="list-group pb-3">
                        <li :class="{ 'list-group-item-danger' : result.no, 'list-group-item-success' : result.yes, 'list-group-item-warning' : !result.no && !result.yes }" class="list-group-item d-flex justify-content-between align-items-start" v-for="(result, index) in results[1]">
                            <div class="pe-1">
                                {{ result.text }}
                            </div>
                            <div class="text-nowrap">
                                <!-- <span class="badge bg-primary rounded-pill">{{ result.sim | round(2) }}</span> -->
                                <a href="#" @click.prevent="setYes(1, index)" class="badge bg-success rounded-pill"><i class="far fa-smile"></i></a>
                                <a href="#" @click.prevent="setUnknown(1, index)" class="badge bg-warning rounded-pill"><i class="far fa-meh"></i></a>
                                <a href="#" @click.prevent="setNo(1, index)" class="badge bg-danger rounded-pill"><i class="far fa-frown"></i></a>
                                <a target="_blank" :href="result.key" class="badge bg-secondary rounded-pill">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-12 text-end">
                    <i v-show="loading_submit" class="fas fa-circle-notch fa-spin"></i>
                    <button class="btn btn-primary" @click.prevent="confirm" :disabled="confirmDisabled">Conferma</button>
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

                "show_help_done": 1,

                "loading_query": false,
                "loading_submit": false,
                "submitDisabled": false,
                "confirmDisabled": false,
                "show_success": false,

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

            this.listSentences()
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
        props: ["axiosApi", "loggedInfo"],
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
        computed: {
            leftChars: function() {
                return Math.max(200 - this.text.length, 0);
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
            },
            show_success: function(newValue) {
                var oldThis = this;
                if (newValue) {
                    setTimeout(function() {
                        oldThis.show_success = false;
                    }, 10000);
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
                    for (var i = 0; i < this.sentences.length; i++) {
                        if (this.sentences[i].id == this.chosen_sentence) {
                            this.text_choice = this.sentences[i].sentence;
                        }
                    }
                    // this.text_choice = this.sentences[this.chosen_sentence].sentence;
                }
                else {
                    this.text_choice = "";
                }
            },
            setUnknown: function(main_index, index) {
                this.results[main_index][index]['yes'] = false;
                this.results[main_index][index]['no'] = false;
                this.$set(this.results, main_index, this.results[main_index]);

                var thisText = this.results[main_index][index]["text"]
                for (var i = 0; i < this.results.length - 1; i++) {
                    for (var j = 0; j < this.results[i].length; j++) {
                        var compText = this.results[i][j]["text"]
                        if (compText == thisText) {
                            this.results[i][j]['yes'] = false;
                            this.results[i][j]['no'] = false;
                        }
                    }
                }

                this.saveSession()
                .then(function (data) {
                    oldThis.session_id = data.data.session_id;
                })
            },
            setYes: function(main_index, index) {
                var oldThis = this;
                this.results[main_index][index]['yes'] = ! this.results[main_index][index]['yes'];
                this.results[main_index][index]['no'] = false;
                this.$set(this.results, main_index, this.results[main_index]);

                var thisText = this.results[main_index][index]["text"]
                for (var i = 0; i < this.results.length - 1; i++) {
                    for (var j = 0; j < this.results[i].length; j++) {
                        var compText = this.results[i][j]["text"]
                        if (compText == thisText) {
                            this.results[i][j]['yes'] = this.results[main_index][index]['yes'];
                            this.results[i][j]['no'] = false;
                        }
                    }
                }

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

                var thisText = this.results[main_index][index]["text"]
                for (var i = 0; i < this.results.length - 1; i++) {
                    for (var j = 0; j < this.results[i].length; j++) {
                        var compText = this.results[i][j]["text"]
                        if (compText == thisText) {
                            this.results[i][j]['no'] = this.results[main_index][index]['no'];
                            this.results[i][j]['yes'] = false;
                        }
                    }
                }

                this.saveSession()
                .then(function (data) {
                    oldThis.session_id = data.data.session_id;
                })
            },
            saveSession: function() {
                var pars = {
                    session_id: this.session_id,
                    value: this.results
                };
                return this.axiosApi.post("?action=setSession", pars);
            },
            listSentences: function() {
                return this.axiosApi.get("?action=listSentences", {});
            },
            shuffle: function(array) {
                let currentIndex = array.length, randomIndex;

                // While there remain elements to shuffle...
                while (currentIndex != 0) {

                    // Pick a remaining element...
                    randomIndex = Math.floor(Math.random() * currentIndex);
                    currentIndex--;

                    // And swap it with the current element.
                    [array[currentIndex], array[randomIndex]] = [array[randomIndex], array[currentIndex]];
                }

                return array;
            },
            confirm: function() {
                var oldThis = this;

                var pars = {
                    session_id: this.session_id
                };
                this.loading_submit = true;
                this.axiosApi.post("?action=confirmSession", pars)
                .then(function (data) {
                    if (data.data.result == "OK") {
                        oldThis.listSentences()
                        .then(function (sdata) {
                            oldThis.sentences = sdata.data.sentences;
                            oldThis.session_id = 0;
                            oldThis.chosen_sentence = -1;
                            oldThis.text = "";
                            oldThis.selectSentence();
                            oldThis.show_success = true;
                            oldThis.show_help_done = 0;
                            window.scrollTo(0, 0);
                            oldThis.results = [];
                            oldThis.axiosApi.get("?action=getLoginInfo", {});
                        })
                        .catch(function (error) {
                            alert(error.response.data.error);
                        })
                    }
                })
                .catch(function (error) {
                    alert(error.response.data.error);
                })
                .finally(function () {
                    oldThis.loading_submit = false;
                })
            },
            go: function() {

                var t = this.text;
                var id = 0;
                switch (this.selected_tab) {
                    case "nav-choice-tab":
                        t = this.text_choice;
                        if (this.chosen_sentence != -1) {
                            // id = this.sentences[this.chosen_sentence].id;
                            id = this.chosen_sentence;
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
                    pars['tokens'] = data.data.tokens;

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

                    oldThis.session_id = 0;
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
