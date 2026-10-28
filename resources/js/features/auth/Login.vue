<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-500 p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 7V5a2 2 0 012-2h6l2 2h6a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-white">ProjectFlow</h1>
        <p class="text-indigo-200 mt-2">Manage your projects like a pro</p>
      </div>

      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Sign in to your account</h2>

        <form @submit.prevent="submit" class="space-y-4">
          <div v-if="error" class="p-3 rounded-lg bg-red-50 text-red-600 text-sm border border-red-200">
            {{ error }}
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <input v-model="form.email" type="email" required autofocus
                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="you@example.com">
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-sm font-medium text-gray-700">Password</label>
              <a href="#" class="text-xs text-indigo-600 hover:text-indigo-800">Forgot password?</a>
            </div>
            <input v-model="form.password" type="password" required
                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="••••••••">
          </div>

          <button type="submit" :disabled="auth.loading"
                  class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
            {{ auth.loading ? 'Signing in...' : 'Sign In' }}
          </button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
          Don't have an account?
          <router-link to="/register" class="font-medium text-indigo-600 hover:text-indigo-800">Register</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const form = reactive({ email: '', password: '' });
const error = ref('');

async function submit() {
  error.value = '';
  const result = await auth.login(form.email, form.password);
  if (result.success) {
    router.push('/');
  } else {
    error.value = result.message;
  }
}
</script>