<script setup>
defineProps({
    teams: Array
})
</script>

<template>
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th class="header">Team Name</th>
                <th class="header">P</th>
                <th class="header">W</th>
                <th class="header">D</th>
                <th class="header">L</th>
                <th class="header">GD</th>
                <th class="header">Points</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="team in teams" :key="team.id">
                <td>{{ team.name }}</td>
                <td>{{ team.played }}</td>
                <td>{{ team.won }}</td>
                <td>{{ team.drawn }}</td>
                <td>{{ team.lost }}</td>
                <td>{{ team.goalDifference }}</td>
                <td>{{ team.points }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.header {
    background-color: black;
    color: white;
}
</style>
