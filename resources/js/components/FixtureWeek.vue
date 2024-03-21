<template>
    <div class="col">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title">Week {{ weekNumber }}</h2>
            </div>
            <ul class="list-group list-group-flush">
                <FixtureMatch
                    v-for="match in matches"
                    :key="match.id"
                    :match="match"
                />
            </ul>
        </div>
    </div>
</template>

<script setup>
import FixtureMatch from './FixtureMatch.vue';
defineProps({
    weekNumber: Number,
    matches: Array
})
</script>
