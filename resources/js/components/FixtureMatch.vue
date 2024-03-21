<template>
    <li class="list-group-item">
        {{ match.home_team }} - {{ match.away_team }}
    </li>
</template>

<script setup>
defineProps({
    match: Object
})
</script>
