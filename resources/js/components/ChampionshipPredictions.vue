<template>
    <div class="card">
        <div class="card-header text-white" style="background-color: black;">
            Championship Predictions
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item" v-for="team in teams" :key="team.id">
                {{ team.name }} <span class="badge bg-primary float-end">-</span>
            </li>
        </ul>
    </div>
</template>

<script setup>
defineProps({
    teams: Array
})
</script>
