<template>
    <div>
        <h1 class='py-5'>
            Opzioni
        </h1>

        <div class="card border-secondary mb-3" v-show="loggedInfo.show_help == '1'">
            <div class="card-header">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="card-body">
                <div class="card-text" style="font-size: .8em;">
                    <p>
                        In questa pagina è possibile:
                    </p>
                    <ul>
                        <li>selezionare da un elenco la disciplina insegnata;</li>
                        <li>disattivare le finestre di aiuto contestuale (come quella che stai leggendo ora);</li>
                        <li>inserire un commento sullo strumento o sull'interfaccia, in modo da aiutarci a migliorarlo.</li>
                    </ul>
                    <p>
                        Al termine della selezione, confermare premendo "Invia".
                    </p>
                </div>
            </div>
        </div>

        <div class="row mb-2">
            <label for="inputTopic" class="col-2 col-form-label">Disciplina:</label>
            <div class="col-9">
                <select v-model="selected_topic" class="form-select">
                    <option value="0">[Nessuna]</option>
                    <option v-for="topic in topics" :value="topic.id">{{ topic.name }}</option>
                </select>
            </div>
        </div>
        <div class="row mb-2">
            <label for="inputNotes" class="col-2 col-form-label">Note:</label>
            <div class="col-10">
                  <textarea class="form-control" id="inputNotes" rows="3" v-model="notes"></textarea>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-10 offset-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" v-model="show_help" id="helpCheck">
                    <label class="form-check-label" for="helpCheck">
                        Visualizza aiuto contestuale
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-10 offset-2">
                <button class="btn btn-primary me-3" @click.prevent="setData" :disabled="loading">Invia</button>
                <i v-show="loading" class="fas fa-circle-notch fa-spin"></i>
                <template v-if="loaded_ok">
                    <i class="fas fa-check"></i> Grazie per l'informazione!
                    <router-link class="alert-link" href="#" to="/">Clicca qui per tornare all'annotazione</router-link>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                topics: {},
                selected_topic: 0,
                show_help: 1,
                loading: true,
                notes: "",
                loaded_ok: false
            };
        },
        mounted: function() {
            var oldThis = this;
            this.axiosApi.get("?action=getTopics")
            .then(function(data) {
                oldThis.topics = data.data.topics;
                oldThis.notes = oldThis.loggedInfo.notes;
                oldThis.selected_topic = oldThis.loggedInfo.topic;
                oldThis.show_help = oldThis.loggedInfo.show_help == '1' ? true : false;
                oldThis.loading = false;
            });
        },
        props: ["axiosApi", "loggedInfo"],
        watch: {
            // loaded_ok: function(newValue) {
            //     var oldThis = this;
            //     if (newValue) {
            //         setInterval(function() {
            //             oldThis.loaded_ok = false;
            //         }, 3000);
            //     }
            // }
        },
        methods: {
            setData: function() {
                var oldThis = this;
                this.loading = true;
                this.loaded_ok = false;
                this.axiosApi.post("?action=setData", {
                    topic: this.selected_topic,
                    show_help: this.show_help,
                    notes: this.notes
                })
                .then(function(data) {
                    this.axiosApi.get("?action=getLoginInfo", {});
                    // nothing to do here
                })
                .finally(function() {
                    oldThis.loading = false;
                    oldThis.loaded_ok = true;
                });
            }
        }
    }
</script>

<style>
</style>
