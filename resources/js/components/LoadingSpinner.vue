<template>
  <div v-if="visible" class="flex items-center justify-center" :class="containerClass">
    <div class="relative" :class="sizeClass">
      <div class="spinner-border animate-spin rounded-full border-4 border-solid border-current border-t-transparent" :class="colorClass"></div>
      <span v-if="message" class="ml-3 text-sm" :class="textClass">{{ message }}</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LoadingSpinner',
  props: {
    visible: {
      type: Boolean,
      default: true
    },
    message: {
      type: String,
      default: ''
    },
    size: {
      type: String,
      default: 'md',
      validator: value => ['sm', 'md', 'lg', 'xl'].includes(value)
    },
    color: {
      type: String,
      default: 'primary'
    },
    containerClass: {
      type: String,
      default: 'p-4'
    }
  },
  computed: {
    sizeClass() {
      const sizes = {
        sm: 'w-4 h-4',
        md: 'w-8 h-8',
        lg: 'w-12 h-12',
        xl: 'w-16 h-16'
      };
      return sizes[this.size] || sizes.md;
    },
    colorClass() {
      const colors = {
        primary: 'border-blue-500',
        success: 'border-green-500',
        danger: 'border-red-500',
        warning: 'border-yellow-500',
        secondary: 'border-gray-500'
      };
      return colors[this.color] || colors.primary;
    },
    textClass() {
      const textColors = {
        primary: 'text-blue-700',
        success: 'text-green-700',
        danger: 'text-red-700',
        warning: 'text-yellow-700',
        secondary: 'text-gray-700'
      };
      return textColors[this.color] || textColors.primary;
    }
  }
};
</script>

<style scoped>
.spinner-border {
  display: inline-block;
  vertical-align: text-bottom;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
