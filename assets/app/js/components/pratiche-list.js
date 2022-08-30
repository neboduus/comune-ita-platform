"use strict";

Vue.component('pratiche-list', {
    template: `<div>
        <el-dialog
                title="Action result"
                v-model="dialogVisible"
                size="tiny">
            <span v-html="dialogMessage"></span>
            <span slot="footer" class="dialog-footer">
                <el-button type="primary" @click="dialogVisible = false">Close</el-button>
            </span>
        </el-dialog>


        <h3>Bind Operatore to ente </h3>
        <el-select v-model="chosenOperatore" placeholder="Operatore (ente)">
            <template v-for="item in operatori">
            <el-option
                    :key="item.id"
                    :label="item.label"
                    :value="item.id">
            </el-option>
            </template>
        </el-select>
        <el-select v-model="chosenEnte" placeholder="Ente">
            <el-option
                    v-for="item in enti"
                    :key="item.id"
                    :label="item.name"
                    :value="item.id">
            </el-option>
        </el-select>
        <br>
        <div id="bindThem" v-if="showOperatoreBindAction">
            Bind {{ chosenOperatore }} to ente {{ chosenEnte }} ? <el-button v-bind:loading="pendingBindingOperation" type="primary" v-on:click="submitOperatoreBinding">Save Changes</el-button>
        </div>
    </div>`,
    data: function () {
        return {
            enti: null,
            operatori: null,
            chosenOperatore: null,
            chosenEnte: null,
            chosenEnteName: null,
            pendingBindingOperation: false,
            dialogVisible: false,
            dialogMessage: null,
        }
    },
    computed: {
        showOperatoreBindAction: function () {
            return this.chosenEnte && this.chosenOperatore;
        }
    },
    created: function() {
        this.loadEntities();
    },
    methods: {
        submitOperatoreBinding: function (e) {
            const self = this;
            self.pendingBindingOperation = true;
            axios.post(`/amministrazione/api/operatori/${self.chosenOperatore}/ente/${self.chosenEnte}`)
                .then(function () {
                    self.operatori = self.operatori.map((item) => {
                        if (item.id === self.chosenOperatore) {
                            item.ente = self.chosenEnte;
                            self.enti.map((e) => {
                                if (e.id === item.ente) {
                                    item.enteName = e.name;
                                }
                            })
                        }
                        return renderOperatoreSelectItem(item);
                    });
                    self.operatori.splice(0, 0); //trigger update
                    self.chosenOperatore = null;
                    self.chosenEnte = null;
                    self.pendingBindingOperation = false;
                    self.dialogVisible = true;
                    self.dialogMessage = "Update performed";
                })
                .catch((e) => {
                    self.pendingBindingOperation = false;
                    self.dialogVisible = true;
                    self.dialogMessage = e.message;
                })
        },
        loadEntities: function () {
            const self = this;
            axios.get('/amministrazione/api/operatori')
                .then(function (response) {
                    if (response.status == 200) {
                        self.operatori = response.data.map((item) => {
                            return renderOperatoreSelectItem(item);
                        });
                    }
                })
            axios.get('/amministrazione/api/enti')
                .then(function (response) {
                    if (response.status == 200) {
                        self.enti = response.data;
                    }
                })

        }
    }
});
const renderOperatoreSelectItem= function(item) {
  const language = document.documentElement.lang.toString();
    item.label = item.fullName + (item.ente ? ` (${item.enteName})` : ` (${Translator.trans('login_type.none', {}, 'messages', language)})`);
    return item;
}
