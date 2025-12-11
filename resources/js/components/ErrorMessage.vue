<template>
  <transition name="fade">
    <div v-if="visible && message" :class="alertClass" role="alert" class="rounded-lg p-4 mb-4 flex items-center justify-between">
      <div class="flex items-center">
        <svg v-if="type === 'danger'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <svg v-if="type === 'warning'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <svg v-if="type === 'success'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <svg v-if="type === 'info'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <div>
          <strong v-if="title" class="font-bold mr-2">{{ title }}</strong>
          <span>{{ message }}</span>
        </div>
      </div>
      <button v-if="dismissible" @click="dismiss" class="ml-4 text-lg font-bold leading-none hover:opacity-75 focus:outline-none" aria-label="Close">
        &times;
      </button>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'ErrorMessage',
  props: {
    message: {
      type: String,
      default: ''
    },
    title: {
      type: String,
      default: ''
    },
    type: {
      type: String,
      default: 'danger',
      validator: value => ['danger', 'warning', 'success', 'info'].includes(value)
    },
    dismissible: {
      type: Boolean,
      default: true
    },
    autoDismiss: {
      type: Number,
      default: 0 // 0 means no auto-dismiss
    }
  },
  data() {
    return {
      visible: true,
      dismissTimer: null
    };
  },
  computed: {
    alertClass() {
      const classes = {
        danger: 'bg-red-100 border border-red-400 text-red-700',
        warning: 'bg-yellow-100 border border-yellow-400 text-yellow-700',
        success: 'bg-green-100 border border-green-400 text-green-700',
        info: 'bg-blue-100 border border-blue-400 text-blue-700'
      };
      return classes[this.type] || classes.danger;
    }
  },
  watch: {
    message(newVal) {
      if (newVal) {
        this.visible = true;
        this.startAutoDismiss();
      }
    }
  },
  mounted() {
    if (this.message) {
      this.startAutoDismiss();
    }
  },
  beforeDestroy() {
    if (this.dismissTimer) {
      clearTimeout(this.dismissTimer);
    }
  },
  methods: {
    dismiss() {
      this.visible = false;
      this.$emit('dismissed');
    },
    startAutoDismiss() {
      if (this.autoDismiss > 0) {
        if (this.dismissTimer) {
          clearTimeout(this.dismissTimer);
        }
        this.dismissTimer = setTimeout(() => {
          this.dismiss();
        }, this.autoDismiss);
      }
    }
  }
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}
</style>
