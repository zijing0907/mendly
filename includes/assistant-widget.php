<?php
// "Ask Mendly" help assistant, included on every page by footer.php.
// Behaviour is in assets/js/assistant.js, answers come from api/assistant.php.
?>
<link rel="stylesheet" href="assets/css/assistant.css">

<button id="assistantLauncher" class="assistant-launcher" type="button" aria-controls="assistantPanel" aria-expanded="false">
    <span aria-hidden="true">✨</span> Ask Mendly
</button>

<section id="assistantPanel" class="assistant-panel" aria-label="Mendly help assistant" hidden>
    <header class="assistant-header">
        <div>
            <strong>Mendly Helper</strong>
            <small>Finds the right repair service for you</small>
        </div>
        <button type="button" class="assistant-close" id="assistantClose" aria-label="Close helper">&times;</button>
    </header>

    <div class="assistant-messages" id="assistantMessages" aria-live="polite">
        <div class="assistant-msg assistant-msg-bot">Hi! Tell me what needs fixing and I'll point you to the right repair expert. I can also explain how requests, prices and pickup work.</div>
    </div>

    <div class="assistant-suggestions" id="assistantSuggestions">
        <button type="button">My bag strap broke</button>
        <button type="button">Can you fix an old teddy bear?</button>
        <button type="button">How do I request a repair?</button>
    </div>

    <form class="assistant-form" id="assistantForm">
        <label for="assistantInput" class="visually-hidden">Your question</label>
        <input id="assistantInput" type="text" maxlength="500" autocomplete="off" placeholder="Ask about a repair...">
        <button type="submit" aria-label="Send">➤</button>
    </form>
    <p class="assistant-note">Answers can be wrong, so please check the service page before you book.</p>
</section>

<script src="assets/js/assistant.js"></script>
