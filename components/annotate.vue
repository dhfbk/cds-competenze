<template>
    <div>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <router-link class="navbar-brand" href="#" to="/">Associazioni competenze</router-link>
                <template v-if="logged_info === undefined || Object.keys(logged_info).length > 0">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <router-link class="nav-link" href="#" to="/" exact>Home</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link class="nav-link" href="#" to="/annotations">Annotazioni</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link class="nav-link" href="#" to="/options">Opzioni</router-link>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" @click="logout">Logout</a>
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </nav>

        <div class="container">
            <div v-if="loading_main" class="text-center">
                <p><i class="fas fa-circle-notch fa-spin"></i> Caricamento in corso...</p>
            </div>
            <div v-else-if="logged_info === undefined || Object.keys(logged_info).length > 0">
                <p class="mt-3">
                    <span class="me-3">
                        Annotazioni completate:
                    </span>
                    <span class="badge bg-primary rounded-pill me-1">
                        {{ "nav-choice-tab" | goodName }}
                    </span>
                    <span class="badge rounded-pill me-3" :class="{ 'bg-secondary': totals['nav-choice-tab'] < limits['nav-choice-tab'], 'bg-success': totals['nav-choice-tab'] >= limits['nav-choice-tab']}">
                        {{ totals['nav-choice-tab'] ? totals['nav-choice-tab'] : 0 }}/{{ limits['nav-choice-tab'] }}
                    </span>
                    <span class="badge bg-primary rounded-pill me-1">
                        {{ "nav-free-tab" | goodName }}
                    </span>
                    <span class="badge rounded-pill me-3" :class="{ 'bg-secondary': totals['nav-free-tab'] < limits['nav-free-tab'], 'bg-success': totals['nav-free-tab'] >= limits['nav-free-tab']}">
                        {{ totals['nav-free-tab'] ? totals['nav-free-tab'] : 0}}/{{ limits['nav-free-tab'] }}
                    </span>
                </p>
                <div class="alert alert-success me-1" v-if="remaining == 0">
                    Hai completato la valutazione del numero di frasi atteso. Essendo ogni valutazione preziosa, volendo puoi prosegire il compito inserendo ulteriori frasi. Grazie!
                </div>
                <div class="alert alert-warning me-1" v-else-if="remaining == 1">
                    Ci sei quasi! Manca ancora <strong>una</strong> frase per raggiungere il nostro obiettivo. Ti chiediamo quindi gentilmente di  proseguire la valutazione inserendo una nuova frase. Grazie!
                </div>
                <div class="alert alert-warning me-1" v-else>
                    Ogni valutazione è per noi importante. Mancano ancora <strong>{{ remaining }}</strong> frasi per raggiungere il nostro obiettivo. Ti chiediamo quindi gentilmente di  proseguire la valutazione inserendo una nuova frase. Grazie!
                </div>
                <router-view :axios-api="axiosApi" :logged-info="logged_info"></router-view>
                <!-- <form-competences :axios-api="axiosApi"></form-competences> -->
            </div>
            <div v-else class="text-center mt-3">
                <h2>Eseguire il login</h2>
                <p>
                    <button @click="enter" class="btn btn-primary">Entra</button>
                </p>
                <p>
                    Consulta il <a href="https://www.youtube.com/watch?v=WLBdY_y51b4" target="_blank">video-tutorial</a> per l'uso della piattaforma.
                </p>
            </div>

            <div class="alert alert-danger" role="alert" v-if="error">
                {{ error }}
            </div>

        </div>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                "logged_info": {},
                "error": "",
                "axiosApi": null,
                "loading_main": true,
                "totals": {},
                "limits": {'nav-free-tab': 5, 'nav-choice-tab': 15}
            };
        },
        computed: {
            remaining: function() {
                return Math.max(0, this.limits['nav-free-tab'] - this.totals['nav-free-tab']) + Math.max(0, this.limits['nav-choice-tab'] - this.totals['nav-choice-tab'])
            }
        },
        components: {
            "form-competences": httpVueLoader('components/form-competences.vue')
        },
        mounted: function() {

            var oldThis = this;

            this.axiosApi = axios.create({
                baseURL: 'api/'
            });
            this.axiosApi.interceptors.request.use(
                (request) => {
                    oldThis.error = "";
                    return request;
                },
                (error) => {
                    oldThis.error = error;
                }
            );
            this.axiosApi.interceptors.response.use(
                (response) => {
                    if (response.data.lastError) {
                        oldThis.error = response.data.lastError;
                    }
                    if (response.data.logged != undefined) {
                        oldThis.logged_info = response.data.logged;
                    }
                    if (response.data.totals != undefined) {
                        oldThis.totals = response.data.totals;
                    }
                    if (response.data.result && response.data.result == "ERR") {
                        oldThis.error = response.data.error;
                        return {};
                    }
                    else {
                        return response;
                    }
                },
                (error) => {
                    oldThis.error = error;
                }
            );
            this.updateLoginInfo()
            .then(function (data) {
                oldThis.loading_main = false;
            })
        },
        props: [],
        filters: {
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
        methods: {
            updateLoginInfo: function() {
                return this.axiosApi.get("?action=getLoginInfo", {});
            },
            enter: function() {
                window.location = "api/?action=login";
            },
            logout: function() {
                var oldThis = this;
                this.axiosApi.get("?action=logout", {})
                .then(function (data) {
                    oldThis.updateLoginInfo();
                })
            }
        }
    }
</script>

<style>
</style>
