<script setup>
import { ref } from 'vue'
import LeagueTable from "../components/LeagueTable.vue";
import ChampionshipPredictions from "../components/ChampionshipPredictions.vue";

defineProps({
    league: Array,
    currentWeekMatches: Array
})

const currentWeek = ref(1)
</script>

<template>
    <div class="container py-5">
        <h1 class="mb-4 text-center">Simulation</h1>
        <div class="row">
            <div class="col-lg-6">
                <LeagueTable :teams="league"/>
            </div>
            <div class="col-lg-3">
                <ChampionshipPredictions :teams="league"/>
            </div>
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header text-white" style="background-color: black;">
                        Week {{ currentWeek }}
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item" v-for="match in currentWeekMatches">
                            {{ match.home_team.name }} - {{ match.away_team.name }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row justify-content-center pt-4">
            <div class="d-grid col-3 gap-2  mx-auto">
                <button type="button" class="btn btn-primary">Play All Weeks</button>
            </div>
            <div class="d-grid col-3 gap-2  mx-auto">
                <button type="button" class="btn btn-primary">Play Next Week</button>
            </div>
            <div class="d-grid col-3 gap-2   mx-auto">
                <button type="button" class="btn btn-danger">Reset Data</button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
