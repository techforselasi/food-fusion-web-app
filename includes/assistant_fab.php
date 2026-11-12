<?php

declare(strict_types=1);

?>
<div class="assistant" id="assistant-root">
  <button
    type="button"
    class="assistant__fab"
    id="assistant-fab"
    aria-expanded="false"
    aria-controls="assistant-panel"
    aria-label="Open kitchen assistant"
  >
    <svg class="assistant__fab-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path
        fill="currentColor"
        d="M4 4h16v12H5.17L4 17.17V4zm0-2c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2H4zm2 10h8v2H6v-2zm0-3h12v2H6V9zm0-3h12v2H6V6z"
      />
    </svg>
  </button>

  <div
    class="assistant__panel"
    id="assistant-panel"
    role="dialog"
    aria-modal="true"
    aria-labelledby="assistant-panel-title"
    aria-hidden="true"
  >
    <div class="assistant__panel-inner">
      <header class="assistant__head">
        <h2 class="assistant__title" id="assistant-panel-title">Kitchen partner</h2>
        <button type="button" class="assistant__close" id="assistant-close" aria-label="Close assistant">
          <span aria-hidden="true">&times;</span>
        </button>
      </header>
      <div class="assistant__messages" id="assistant-messages" aria-live="polite"></div>
      <form class="assistant__form" id="assistant-form">
        <label class="visually-hidden" for="assistant-input">Message</label>
        <textarea
          id="assistant-input"
          class="assistant__input input-box"
          name="message"
          rows="3"
          maxlength="2000"
          placeholder="Ask a cooking question…"
          required
        ></textarea>
        <button type="submit" class="assistant__send btn btn--primary" id="assistant-send">Send</button>
      </form>
      <p class="assistant__disclaimer">AI can make mistakes. Double-check food safety and allergies.</p>
    </div>
  </div>
</div>
