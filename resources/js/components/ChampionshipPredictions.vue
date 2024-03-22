<template>
    <div class="card">
        <div class="card-header text-white" style="background-color: black;">
            Championship Predictions
        </div>
        <ul v-if="teams != null && teams !== undefined" class="list-group list-group-flush">
            <li class="list-group-item" v-for="team in teams" :key="team.id">
                {{ team.team_name }} <span class="badge bg-primary float-end"> {{ team.championship_rate }} %</span>
            </li>
        </ul>
        <ul v-else class="list-group list-group-flush">
            <p class="text-center mt-3">There is no predicts</p>
        </ul>
    </div>
</template>

<script setup>
defineProps({
    teams: Array
})
</script>
