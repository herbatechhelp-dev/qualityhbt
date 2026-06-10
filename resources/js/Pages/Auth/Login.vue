<script setup>
import AuthContainer from './AuthContainer.vue';

defineProps({
    status: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <AuthContainer initial-mode="login" :status="status" />
</template>
