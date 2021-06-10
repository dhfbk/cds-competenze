<template>
    <div>
        <h1 class='py-5'>
            Opzioni
        </h1>
        <div class="mb-3 row">
            <label for="inputTopic" class="col-2 col-form-label">Disciplina:</label>
            <div class="col-9">
                <select v-model="selected_topic" class="form-select" aria-label="Default select example" @change="setTopic">
                    <option value="0">[Nessuna]</option>
                    <option v-for="topic in topics" :value="topic.id">{{ topic.name }}</option>
                </select>
            </div>
            <div class="col-1 col-form-label">
                <i v-show="loading" class="fas fa-circle-notch fa-spin"></i>
                <i v-show="loaded_ok" class="fas fa-check"></i>
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
                loading: false,
                loaded_ok: false
            };
        },
        mounted: function() {
            var oldThis = this;
            this.axiosApi.get("?action=getTopics")
            .then(function(data) {
                oldThis.topics = data.data.topics;
                oldThis.selected_topic = oldThis.loggedInfo.topic;
            });
        },
        props: ["axiosApi", "loggedInfo"],
        watch: {
            loaded_ok: function(newValue) {
                var oldThis = this;
                if (newValue) {
                    setInterval(function() {
                        oldThis.loaded_ok = false;
                    }, 1000);
                }
            }
        },
        methods: {
            setTopic: function() {
                var oldThis = this;
                this.loading = true;
                this.loaded_ok = false;
                this.axiosApi.post("?action=setTopic", {
                    topic: this.selected_topic
                })
                .then(function(data) {
                    oldThis.loading = false;
                    oldThis.loaded_ok = true;
                });
            }
        }
    }
</script>

<style>
</style>
