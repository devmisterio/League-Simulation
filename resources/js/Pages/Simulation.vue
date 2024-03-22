<script setup>
import {ref, computed} from 'vue'
import LeagueTable from "../components/LeagueTable.vue";
import ChampionshipPredictions from "../components/ChampionshipPredictions.vue";
import axios from "axios";

const props = defineProps({
    league: Array,
    currentWeekMatches: Array,
    predicts: Object
});

let leagueData = ref(props.league);
const matchesData = ref(props.currentWeekMatches);
let predictsData = ref(props.predicts);
const isLoading = ref(false);

const shouldDisableButton = computed(() => {
    return leagueData.value.length > 0 && leagueData.value[0].played === 6;
});

function playWeek() {
    isLoading.value = true;
    axios.get('/play-week')
        .then(res => {
            leagueData.value = res.data.league;
            matchesData.value = res.data.currentWeekMatches;
            predictsData.value = res.data.predicts;
            console.log(res.data)
        })
        .finally(() => {
            isLoading.value = false;
        });
}

function playAllyWeek() {
    isLoading.value = true;
    axios.get('/play-all-week')
        .then(res => {
            leagueData.value = res.data.league;
            matchesData.value = res.data.currentWeekMatches;
            predictsData.value = res.data.predicts;

            console.log(res)
        })
        .finally(() => {
            isLoading.value = false;
        });
}

</script>

<template>
    <div v-if="isLoading" class="d-flex justify-content-center my-3">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    <div class="container py-5">
        <h1 class="mb-4 text-center">Simulation</h1>
        <div class="row">
            <div class="col-lg-6">
                <LeagueTable :teams="leagueData"/>
            </div>
            <div class="col-lg-3">
                <ChampionshipPredictions :teams="predictsData"/>
            </div>
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header text-white" style="background-color: black;">
                        Week {{ matchesData[0]?.week_number }}
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item" v-for="match in matchesData">
                            {{ match.home_team.name }} - {{ match.away_team.name }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row justify-content-center pt-4">
            <div class="d-grid col-3 gap-2  mx-auto">
                <button :disabled="shouldDisableButton" @click="playAllyWeek" type="button" class="btn btn-primary">Play All Weeks</button>
            </div>
            <div class="d-grid col-3 gap-2  mx-auto">
                <button :disabled="shouldDisableButton" @click="playWeek" type="button" class="btn btn-primary">Play Next Week</button>
            </div>
            <div class="d-grid col-3 gap-2   mx-auto">
                <a class="btn btn-danger" href="/fixtures" role="button">Reset Data</a>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
