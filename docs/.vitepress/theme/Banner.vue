<script setup>
import {withBase} from 'vitepress'
</script>

<template>
  <div class="xbb-banner">
    <span class="xbb-banner__pill">Under Development</span>
    <span class="xbb-banner__text">
      You're reading the docs for the upcoming <strong>XBackBone&nbsp;4.x</strong>.
      Version <strong>3.x</strong> docs are available <a :href="withBase('/v3')">here</a>.
      <!-- Version <strong>3.x</strong> is now retired —    <a :href="withBase('/guide/legacy-import')">migrate your instance</a>.-->
    </span>
  </div>
</template>

<style scoped>
.xbb-banner {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: var(--vp-z-index-layout-top);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: var(--vp-layout-top-height);
  padding: 4px 24px;
  font-size: 13px;
  line-height: 1.35;
  text-align: center;
  color: #fff;
  background: linear-gradient(90deg, #1f6fe8 0%, #1aa260 100%);
}

.xbb-banner__pill {
  flex-shrink: 0;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.22);
}

.xbb-banner__text a {
  color: #fff;
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.xbb-banner__text a:hover {
  opacity: 0.85;
}
</style>
