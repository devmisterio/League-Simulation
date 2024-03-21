<template>
    <div class="mx-5 my-5">
        <div class="fixtures-container my-4">
            <h1 class="text-center mb-4">Generated Fixtures</h1>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <FixtureWeek
                    v-for="(matches, week, index) in weeklyFixtures"
                    :key="week"
                    :weekNumber="index + 1"
                    :matches="matches"
                />
            </div>
        </div>
        <a class="mt-4 btn btn-primary" href="/simulation" role="button">Start Simulation</a>
    </div>
</template>

<script setup>
import FixtureWeek from '../components/FixtureWeek.vue';
defineProps({
    weeklyFixtures: Object
})
</script>
