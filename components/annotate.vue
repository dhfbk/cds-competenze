<template>
    <div>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <router-link class="navbar-brand" href="#" to="/">Cartella dello studente</router-link>
                <template v-show="logged_info === undefined || Object.keys(logged_info).length > 0">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <router-link class="nav-link" href="#" to="/">Match</router-link>
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
                <div v-show="logged_info === undefined || logged_info.topic == 0" class="alert alert-warning">
                    Non hai ancora inserito la tua disciplina. <router-link class="alert-link" href="#" to="/options">Clicca qui per farlo</router-link>
                </div>
                <router-view :axios-api="axiosApi" :logged-info="logged_info"></router-view>
                <!-- <form-competences :axios-api="axiosApi"></form-competences> -->
            </div>
            <div v-else class="text-center mt-3">
                <h2>Eseguire il login</h2>
                <p>
                    <button @click="enter" class="btn btn-primary">Entra</button>
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
                "loading_main": true
            };
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
                    oldThis.logged_info = response.data.logged;
                    return response;
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
