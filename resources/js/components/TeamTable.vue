<template>
    <div class="table-responsive-xl">
        <table class="table team-table shadow-sm rounded">
            <thead>
            <tr>
                <th class="header">Team Name</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="team in teams" :key="team.id">
                <td>{{ team.name }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineProps({
    teams: Array
})
</script>

<style scoped>
.team-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

.header {
    background-color: black;
    color: white;
}
</style>
