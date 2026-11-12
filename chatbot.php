<?php

declare(strict_types=1);

/**
 * FoodFusion — Fusion Assistant (markup only).
 * Link chatbot.css in <head> and chatbot.js before </body> (defer).
 * Include from footer: require __DIR__ . '/chatbot.php';
 */
?>
<div class="fusion-chat" id="fusion-chat-root" data-fusion-chat>
  <button
    type="button"
    class="fusion-chat__launcher"
    id="fusion-chat-launcher"
    aria-expanded="false"
    aria-controls="fusion-chat-window"
    aria-label="Open Fusion Assistant"
  >
    <svg class="fusion-chat__launcher-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path
        fill="currentColor"
        d="M4 4h16v12H5.17L4 17.17V4zm0-2c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2H4zm2 10h8v2H6v-2zm0-3h12v2H6V9zm0-3h12v2H6V6z"
      />
    </svg>
  </button>

  <div
    class="fusion-chat__window"
    id="fusion-chat-window"
    role="dialog"
    aria-modal="true"
    aria-labelledby="fusion-chat-title"
    aria-hidden="true"
  >
    <div class="fusion-chat__card">
      <header class="fusion-chat__header">
        <h2 class="fusion-chat__title" id="fusion-chat-title">Fusion Assistant</h2>
        <button type="button" class="fusion-chat__close" id="fusion-chat-close" aria-label="Close assistant">
          <svg class="fusion-chat__close-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path
              fill="currentColor"
              d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"
            />
          </svg>
        </button>
      </header>

      <div class="fusion-chat__messages" id="fusion-chat-messages" aria-live="polite"></div>

      <form class="fusion-chat__form" id="fusion-chat-form">
        <label class="fusion-chat__label visually-hidden" for="fusion-chat-input">Message</label>
        <input
          type="text"
          class="fusion-chat__input"
          id="fusion-chat-input"
          name="message"
          autocomplete="off"
          maxlength="2000"
          placeholder="Type a message…"
        />
        <button type="submit" class="fusion-chat__send" id="fusion-chat-send" aria-label="Send message">
          <svg class="fusion-chat__send-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path
              fill="currentColor"
              d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"
            />
          </svg>
        </button>
      </form>
    </div>
  </div>
</div>
