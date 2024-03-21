<template>
    <div class="tournament-container bg-body-tertiary">
        <main class="main-content">
            <h1 class="title display-4">Tournament Teams</h1>
            <TeamTable :teams="teams" />
            <a class="btn btn-primary" href="/fixtures" role="button">Generate Fixtures</a>
        </main>
    </div>
</template>

<script setup>
import TeamTable from '../components/TeamTable.vue'

defineProps({
    teams: Array
})
</script>

<style scoped>
.tournament-container {
    display: flex;
    align-items: center;
    height: 100vh;
}

.main-content {
    margin: auto;
    max-width: 800px;
    padding: 1rem;
}

.title {
    padding-bottom: 1.5rem;
}
</style>
