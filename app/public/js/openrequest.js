/**
 * openrequest.php — cascade dropdowns + AJAX intake workflow.
 *
 * Race-condition protection
 * -------------------------
 * intakeGeneration is incremented every time the workflow context changes
 * (catalogue/service change, or an earlier answer changes).  Each in-flight
 * fetch captures the generation at the time it starts; the response is
 * discarded when those numbers differ, preventing stale responses from
 * overwriting newer ones.  An AbortController cancels the current fetch when
 * the generation advances.
 *
 * Aborting a fetch() only stops the browser from reading the response — it
 * does not guarantee the PHP request stopped executing on the server. Each
 * run therefore also carries a monotonic revision counter; every action=step
 * submission echoes back the last revision it observed, and the server
 * rejects the write if that no longer matches (see intake-flow.php). This is
 * the actual guard against overlapping/out-of-order session writes; the
 * generation counter here is only a client-side UX optimisation.
 *
 * No-JS fallback
 * --------------
 * The data-intake-autostart button (rendered by addrequest2-ajax1/2/3.php)
 * is a real type="submit" button pointing at intake-flow.php.  With JS this
 * button is hidden and the flow starts via fetch().  Without JS the button
 * remains visible and works as a standard form submission. Likewise, every
 * question rendered by the no-JS full-path view (rmt_intake_render_full_path)
 * is its own real <form> posting to intake-flow.php.
 */

// ============================================================================
// Intake state (module-level)
// ============================================================================

var intakeRunToken    = null;   // opaque 32-hex run token
var intakeRunCsrf     = null;   // per-run CSRF token
var intakeRunRevision = 0;      // last revision observed from the server
var intakeGeneration  = 0;      // bumped on every context change
var intakeAbortCtrl   = null;   // AbortController for in-flight fetch

// ============================================================================
// Utility
// ============================================================================

function showElement(id, visible) {
    var node = document.getElementById(id);
    if (!node) { return; }
    node.style.display = visible ? '' : 'none';
}

function clearLegacySelectors() {
    $('.divservice').empty();
    $('.divsubservice').empty();
    $('.divsubservice2').empty();
    $('.divsubservice3').empty();
}

function setTrackableCatalogue(catalogueId) {
    document.getElementById('catalogueid').value = catalogueId;
    showElement('guidance-only', false);
    ajax1(catalogueId);
}

function showGuidanceOnly(html) {
    document.getElementById('catalogueid').value = '';
    clearLegacySelectors();
    document.getElementById('guidance-only-content').innerHTML = html;
    showElement('guidance-only', true);
    document.getElementById('guidance-only').focus();
}

// Called when the top-level service-type dropdown changes.
function onStreamChange(stream) {
    clearLegacySelectors();
    clearIntakeWorkflow();
    showElement('guidance-only', false);
    document.getElementById('catalogueid').value = '';

    if (!stream || !stream.startsWith('catalogue_')) { return; }
    var catId = parseInt(stream.replace('catalogue_', ''), 10);
    if (isNaN(catId)) { return; }
    setTrackableCatalogue(catId);
}

function escHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Look up a client-facing string from window.RMT_INTAKE_STRINGS (rendered by
 * openrequest.php from the current language file) instead of hardcoding
 * English/French text in this file. Falls back to the given default only if
 * the server did not provide the key (defensive, should not normally happen).
 */
function intakeString(key, fallback) {
    var strings = window.RMT_INTAKE_STRINGS || {};
    return (typeof strings[key] === 'string' && strings[key] !== '') ? strings[key] : fallback;
}

// ============================================================================
// Intake workflow — generation management
// ============================================================================

/**
 * Bump the generation counter and abort any in-flight fetch.
 * Called whenever the context changes so stale responses are ignored.
 */
function intakeBumpGeneration() {
    intakeGeneration++;
    if (intakeAbortCtrl) {
        intakeAbortCtrl.abort();
        intakeAbortCtrl = null;
    }
}

/**
 * Clear the workflow area and discard the server-side run (best-effort).
 */
function clearIntakeWorkflow() {
    var wf = document.getElementById('intake-workflow');
    if (intakeRunToken) {
        var fd = new FormData();
        fd.append('_ajax',      '1');
        fd.append('action',     'restart');
        fd.append('run_token',  intakeRunToken);
        fd.append('csrf_token', intakeRunCsrf || '');
        fetch('/intake-flow.php', { method: 'POST', body: fd }).catch(function(){});
    }
    intakeBumpGeneration();
    if (wf) { wf.innerHTML = ''; }
    intakeRunToken    = null;
    intakeRunCsrf     = null;
    intakeRunRevision = 0;
    intakeShowStartOver(null, null);
}

function appendIntakeFragment(html) {
    var wf = document.getElementById('intake-workflow');
    if (!wf) { return null; }
    wf.insertAdjacentHTML('beforeend', html);
    hydrateIntakePath(wf);
    return wf.lastElementChild;
}

/**
 * Remove the complete rendered branch after a question. Every renderer path
 * now exposes .intake-path-item as a direct #intake-workflow child, so this
 * works identically for incremental AJAX fragments and reconstructed paths.
 */
function removeFollowingIntakePathItems(nodeContainer) {
    var pathItem = nodeContainer ? nodeContainer.closest('.intake-path-item') : null;
    if (!pathItem) { return; }
    $(pathItem).nextAll('.intake-path-item').remove();
}

function setFollowingIntakePathItemsDisabled(nodeContainer, disabled) {
    var pathItem = nodeContainer ? nodeContainer.closest('.intake-path-item') : null;
    if (!pathItem) { return; }

    var sibling = pathItem.nextElementSibling;
    while (sibling) {
        if (sibling.classList.contains('intake-path-item')) {
            sibling.inert = disabled;
            sibling.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        }
        sibling = sibling.nextElementSibling;
    }
}

function focusIntakePathItem(pathItem) {
    if (!pathItem) { return; }
    var target = pathItem.querySelector('select, input:not([type="hidden"]), textarea, button, a[href]');
    if (target) {
        target.focus();
        return;
    }
    pathItem.setAttribute('tabindex', '-1');
    pathItem.focus();
}

function getIntakeNodeSelection(nodeContainer) {
    var select = nodeContainer.querySelector('.intake-question-select');
    var radio  = select ? null : nodeContainer.querySelector('.intake-question-radio:checked');
    var control = select || radio;
    var option  = select && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : radio;
    var value   = control ? control.value : '';

    return {
        control: control,
        option: option,
        value: value,
        hasFreeform: !!(value && option && option.dataset.allowFreeform === '1'),
        freeformId: option ? (option.dataset.freeformId || '') : '',
        freeformRequired: !!(option && option.dataset.freeformRequired === '1')
    };
}

/**
 * Synchronize every answer-dependent control in one question from its current
 * select/radio value: free-form visibility, required semantics, and the one
 * enhanced Continue control. This is used after hydration, every selection
 * change, placeholder restoration, and failed submissions.
 */
function syncIntakeNodeControls(nodeContainer) {
    var selection = getIntakeNodeSelection(nodeContainer);
    var groups = nodeContainer.querySelectorAll('.intake-freeform-group');

    for (var i = 0; i < groups.length; i++) {
        var group = groups[i];
        var show = selection.hasFreeform && group.id === selection.freeformId;
        group.style.display = show ? '' : 'none';

        var textareas = group.querySelectorAll('textarea');
        for (var j = 0; j < textareas.length; j++) {
            var required = show && selection.freeformRequired;
            textareas[j].required = required;
            if (required) {
                textareas[j].setAttribute('aria-required', 'true');
            } else {
                textareas[j].removeAttribute('aria-required');
            }
            if (!show || textareas[j].value.trim() !== '') {
                textareas[j].setCustomValidity('');
            }
        }
    }

    var continueWrap = nodeContainer.querySelector('.intake-freeform-submit');
    if (continueWrap) {
        continueWrap.style.display = selection.hasFreeform ? '' : 'none';
    }

    return selection;
}

function restoreCommittedNodeState(nodeContainer) {
    var committed = nodeContainer.dataset.committedOption || '';
    var select = nodeContainer.querySelector('.intake-question-select');
    if (select) {
        select.value = committed;
    } else {
        var radios = nodeContainer.querySelectorAll('.intake-question-radio');
        for (var i = 0; i < radios.length; i++) {
            radios[i].checked = committed !== '' && radios[i].value === committed;
        }
    }

    var textareas = nodeContainer.querySelectorAll('.intake-freeform-text');
    for (var j = 0; j < textareas.length; j++) {
        textareas[j].value = textareas[j].dataset.committedValue || '';
    }
    setFollowingIntakePathItemsDisabled(nodeContainer, false);
    return syncIntakeNodeControls(nodeContainer);
}

function updateCommittedNodeState(nodeContainer, optionId, freeformText) {
    nodeContainer.dataset.committedOption = String(optionId);

    var textareas = nodeContainer.querySelectorAll('.intake-freeform-text');
    for (var i = 0; i < textareas.length; i++) {
        textareas[i].dataset.committedValue = '';
    }

    var selection = getIntakeNodeSelection(nodeContainer);
    if (selection.hasFreeform && selection.freeformId) {
        var group = document.getElementById(selection.freeformId);
        var textarea = group ? group.querySelector('.intake-freeform-text') : null;
        if (textarea) {
            textarea.dataset.committedValue = freeformText || '';
        }
    }
}

function clearIntakeNodeValidation(nodeContainer) {
    var summary = nodeContainer.querySelector('.intake-validation-summary');
    if (summary) { summary.remove(); }

    var messages = nodeContainer.querySelectorAll('.intake-field-error');
    for (var i = 0; i < messages.length; i++) {
        messages[i].remove();
    }

    var invalidFields = nodeContainer.querySelectorAll('[aria-invalid="true"]');
    for (var j = 0; j < invalidFields.length; j++) {
        invalidFields[j].removeAttribute('aria-invalid');
        invalidFields[j].removeAttribute('aria-describedby');
    }
}

/**
 * Enhance PHP-rendered fallback forms without changing their no-JS markup.
 * Once JS is running, the fallback submit button becomes the same conditional
 * free-form Continue control used by AJAX fragments; non-freeform questions
 * show no extra button. The operation is idempotent for newly appended paths.
 */
function hydrateIntakePath(root) {
    var pathItems = root.querySelectorAll('.intake-path-item');
    for (var i = 0; i < pathItems.length; i++) {
        var item = pathItems[i];
        var nodeContainer = item.querySelector('.intake-node');
        if (!nodeContainer) { continue; }

        var fallbackForm = item.querySelector('.intake-nojs-form');
        if (fallbackForm && fallbackForm.dataset.intakeEnhanced !== 'true') {
            fallbackForm.dataset.intakeEnhanced = 'true';
            var fallbackWrap = fallbackForm.querySelector('.intake-nojs-submit');
            var fallbackButton = fallbackWrap ? fallbackWrap.querySelector('button') : null;
            if (fallbackWrap && fallbackButton) {
                fallbackWrap.classList.add('intake-freeform-submit');
                fallbackButton.type = 'button';
                fallbackButton.classList.add('intake-ff-continue');
            }
        }

        syncIntakeNodeControls(nodeContainer);
    }
}

/**
 * Show/hide the Start Over control and keep its hidden fields in sync with
 * the active run so it works whether or not JS submits it.
 */
function intakeShowStartOver(token, csrf) {
    var wrap = document.getElementById('intake-start-over');
    if (!wrap) { return; }
    var tokenEl = document.getElementById('intake-start-over-token');
    var csrfEl  = document.getElementById('intake-start-over-csrf');
    if (tokenEl) { tokenEl.value = token || ''; }
    if (csrfEl)  { csrfEl.value  = csrf  || ''; }
    wrap.style.display = token ? '' : 'none';
    if (!token) { showIntakeStartOverError(''); }
}

/**
 * Enable/disable every control currently rendered in the workflow area.
 * Used to prevent overlapping submissions while a state-changing request
 * (answering or changing a question) is in flight.
 */
function intakeSetControlsDisabled(disabled) {
    var wf = document.getElementById('intake-workflow');
    if (!wf) { return; }
    var controls = wf.querySelectorAll('select, input, textarea, button');
    for (var i = 0; i < controls.length; i++) {
        controls[i].disabled = disabled;
    }
    var restartButton = document.querySelector('#intake-start-over button[type="submit"]');
    if (restartButton) {
        restartButton.disabled = disabled;
    }
}

// Show an error in #intake-workflow with a Retry button.
function showIntakeStartError(msg) {
    var wf = document.getElementById('intake-workflow');
    if (!wf) { return; }
    wf.innerHTML = '<div class="alert alert-danger" role="alert">'
        + '<p>' + escHtml(msg) + '</p>'
        + '<button type="button" class="btn btn-default intake-retry-btn">' + escHtml(intakeString('retry', 'Retry')) + '</button>'
        + '</div>';
}

function showIntakeNodeError(nodeContainer, msg) {
    var zone = nodeContainer.querySelector('.intake-error-zone');
    if (zone) { zone.textContent = msg; }
}

function showIntakeStartOverError(msg) {
    var zone = document.getElementById('intake-start-over-error');
    if (zone) { zone.textContent = msg || ''; }
}

function showIntakeStatus(msg) {
    var zone = document.getElementById('intake-status');
    if (zone) { zone.textContent = msg || ''; }
}

/**
 * Discard the active run only after the server confirms action=restart.
 * A failure leaves the complete workflow intact and exposes a localized
 * error next to the Start Over control.
 */
function restartIntakeWorkflow() {
    if (!intakeRunToken) { return; }

    showIntakeStartOverError('');
    intakeSetControlsDisabled(true);
    intakeBumpGeneration();
    var gen = intakeGeneration;

    var fd = new FormData();
    fd.append('_ajax',      '1');
    fd.append('action',     'restart');
    fd.append('run_token',  intakeRunToken);
    fd.append('csrf_token', intakeRunCsrf || '');

    var ctrl = new AbortController();
    intakeAbortCtrl = ctrl;

    fetch('/intake-flow.php', { method: 'POST', body: fd, signal: ctrl.signal })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (intakeGeneration !== gen) { return; }
            intakeAbortCtrl = null;
            if (!data.success) {
                intakeSetControlsDisabled(false);
                showIntakeStartOverError(data.message || intakeString('error_generic', 'Error. Please try again.'));
                var restartButton = document.querySelector('#intake-start-over button[type="submit"]');
                if (restartButton) { restartButton.focus(); }
                return;
            }

            var wf = document.getElementById('intake-workflow');
            if (wf) { wf.innerHTML = ''; }
            intakeRunToken    = null;
            intakeRunCsrf     = null;
            intakeRunRevision = 0;
            intakeShowStartOver(null, null);
            showIntakeStatus(intakeString('restart_complete', 'The intake workflow has been reset.'));

            var cascadeForm = document.getElementById('openrequest-cascade');
            if (cascadeForm) { cascadeForm.reset(); }
            clearLegacySelectors();
            showElement('guidance-only', false);
            document.getElementById('catalogueid').value = '';
            var serviceStream = document.getElementById('service_stream');
            if (serviceStream) { serviceStream.focus(); }
        })
        .catch(function(err) {
            if (intakeGeneration !== gen || (err && err.name === 'AbortError')) { return; }
            intakeAbortCtrl = null;
            intakeSetControlsDisabled(false);
            showIntakeStartOverError(intakeString('error_network', 'Network error. Please try again.'));
        });
}

// ============================================================================
// Intake workflow — start
// ============================================================================

function startIntakeFlow() {
    var gen  = ++intakeGeneration;
    var form = document.getElementById('openrequest-cascade');
    if (!form) { return; }

    var catEl  = form.querySelector('[name="catalogueid"]');
    var svcEl  = form.querySelector('[name="serviceid"]');
    var subEl  = form.querySelector('[name="subserviceid"]');
    var csrfEl = form.querySelector('[name="form_csrf"]');
    if (!csrfEl || !catEl) { return; }

    var fd = new FormData();
    fd.append('_ajax',       '1');
    fd.append('action',      'start');
    fd.append('form_csrf',   csrfEl.value);
    fd.append('catalogueid', catEl.value);
    if (svcEl)  { fd.append('serviceid',    svcEl.value  || ''); }
    if (subEl)  { fd.append('subserviceid', subEl.value  || ''); }

    var ctrl         = new AbortController();
    intakeAbortCtrl  = ctrl;

    fetch('/intake-flow.php', { method: 'POST', body: fd, signal: ctrl.signal })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (intakeGeneration !== gen) { return; } // stale
            intakeAbortCtrl = null;
            if (data.success) {
                showIntakeStartOverError('');
                showIntakeStatus('');
                intakeRunToken    = data.run_token;
                intakeRunCsrf     = data.csrf;
                intakeRunRevision = data.revision || 0;
                intakeShowStartOver(intakeRunToken, intakeRunCsrf);
                appendIntakeFragment(data.fragment);
            } else {
                showIntakeStartError(data.message || intakeString('error_generic', 'Error.'));
            }
        })
        .catch(function(err) {
            if (intakeGeneration !== gen || (err && err.name === 'AbortError')) { return; }
            intakeAbortCtrl = null;
            showIntakeStartError(intakeString('error_network', 'Network error. Please try again.'));
        });
}

// ============================================================================
// Intake workflow — answer a question
// ============================================================================

function onIntakeAnswerChange(el, optionId) {
    if (!intakeRunToken) { return; }

    var nodeContainer = el.closest('.intake-node');
    if (!nodeContainer) { return; }
    var nodeId = nodeContainer.dataset.nodeId;
    if (!nodeId) { return; }

    if (!optionId) {
        // Empty is not a server-side clear action. Restore the last committed
        // answer and keep its rendered branch so browser and server state
        // cannot diverge.
        restoreCommittedNodeState(nodeContainer);
        return;
    }

    var selection = syncIntakeNodeControls(nodeContainer);

    // A freeform selection is staged until Continue is activated. Keep the
    // previously committed branch visible, but make it inoperable so no
    // downstream write can race ahead of the uncommitted parent answer.
    setFollowingIntakePathItemsDisabled(nodeContainer, selection.hasFreeform);

    // For non-freeform options, auto-submit immediately
    if (!selection.hasFreeform) {
        submitIntakeAnswer(el, nodeContainer, nodeId, optionId, null);
    }
    // For freeform options, wait for the Continue button click
}

/**
 * Submit an answer to intake-flow.php via AJAX.
 *
 * Transactional behaviour:
 *  - Downstream nodes are only removed after the server confirms success;
 *    on failure or network error they are left exactly as they were.
 *  - The previously committed option is restored on the trigger control if
 *    the submission fails, rather than leaving the rejected in-progress
 *    value showing.
 *  - Every control in #intake-workflow is disabled while the request is in
 *    flight, not just the one that was changed, so a second overlapping
 *    change cannot be started from elsewhere in the workflow.
 *  - The server independently guards against overlapping writes via a
 *    per-run revision counter (see intake-flow.php); aborting this fetch
 *    client-side does not by itself stop an earlier request from finishing
 *    on the server.
 *
 * @param {HTMLElement} triggerEl   The select/radio/button that initiated the submit.
 * @param {HTMLElement} nodeContainer  .intake-node parent.
 * @param {string}      nodeId
 * @param {string}      optionId
 * @param {string|null} freeformText  Only for the selected freeform option.
 */
function submitIntakeAnswer(triggerEl, nodeContainer, nodeId, optionId, freeformText) {
    if (!intakeRunToken) { return; }

    var selection = syncIntakeNodeControls(nodeContainer);
    if (selection.hasFreeform && selection.freeformRequired
        && (freeformText === null || freeformText === '')) {
        var requiredGroup = document.getElementById(selection.freeformId);
        var requiredField = requiredGroup ? requiredGroup.querySelector('.intake-freeform-text') : null;
        if (requiredField) {
            requiredField.required = true;
            requiredField.setCustomValidity(intakeString(
                'error_freeform_required',
                'Please enter text in this field.'
            ));
            requiredField.focus();
            requiredField.reportValidity();
        }
        return;
    }

    // Prevent duplicate submissions
    if (intakeAbortCtrl) {
        intakeAbortCtrl.abort();
        intakeAbortCtrl = null;
    }

    var gen           = ++intakeGeneration;
    var revisionAtReq = intakeRunRevision;

    // Clear any inline error
    var zone = nodeContainer.querySelector('.intake-error-zone');
    if (zone) { zone.textContent = ''; }
    clearIntakeNodeValidation(nodeContainer);

    // Disable every workflow control while this state-changing request is
    // pending; downstream content is intentionally left in place until the
    // server confirms the change.
    intakeSetControlsDisabled(true);

    var fd = new FormData();
    fd.append('_ajax',           '1');
    fd.append('action',          'step');
    fd.append('run_token',       intakeRunToken);
    fd.append('csrf_token',      intakeRunCsrf || '');
    fd.append('node_id',         nodeId);
    fd.append('option_id',       optionId);
    fd.append('client_revision', String(revisionAtReq));
    if (freeformText !== null) {
        fd.append('freeform_opt_' + optionId, freeformText);
    }

    var ctrl        = new AbortController();
    intakeAbortCtrl = ctrl;

    fetch('/intake-flow.php', { method: 'POST', body: fd, signal: ctrl.signal })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (intakeGeneration !== gen) { return; } // superseded by a newer request
            intakeAbortCtrl = null;
            intakeSetControlsDisabled(false);
            if (data.success) {
                // Only now remove any existing downstream nodes and append
                // the new branch — the change is confirmed.
                removeFollowingIntakePathItems(nodeContainer);
                updateCommittedNodeState(nodeContainer, optionId, freeformText);
                intakeRunRevision = (typeof data.revision === 'number') ? data.revision : (intakeRunRevision + 1);
                syncIntakeNodeControls(nodeContainer);
                var appendedPathItem = appendIntakeFragment(data.fragment);
                focusIntakePathItem(appendedPathItem);
            } else {
                restoreCommittedNodeState(nodeContainer);
                showIntakeNodeError(nodeContainer,
                    data.message || intakeString('error_generic', 'Error. Please try again.'));
                if (triggerEl) { triggerEl.focus(); }
            }
        })
        .catch(function(err) {
            if (intakeGeneration !== gen || (err && err.name === 'AbortError')) { return; }
            intakeAbortCtrl = null;
            intakeSetControlsDisabled(false);
            restoreCommittedNodeState(nodeContainer);
            showIntakeNodeError(nodeContainer, intakeString('error_network', 'Network error. Please try again.'));
            if (triggerEl) { triggerEl.focus(); }
        });
}

// ============================================================================
// Intake workflow — language switch
// ============================================================================
//
// Language switching is a full page navigation to the real header link
// (openrequest.php?lang=TARGET, with &run=TOKEN appended when a run is
// active) — see the click handler on '#wb-lng a[lang], .lng-ofr a[lang]'
// registered in the document-ready block below. openrequest.php reconstructs
// the validated session path for that run on reload. There is deliberately
// no AJAX fragment-swap for language switching: browser history/back-forward
// state should never be trusted to reflect the current run, and a full
// reload is the only way to guarantee the rendered page, the session, and
// the run token all agree.

// ============================================================================
// Cascade dropdowns
// ============================================================================

function afterCascadeInject(jqContainer) {
    if (jqContainer.find('[data-intake-autostart]').length) {
        jqContainer.find('[data-intake-autostart] button').hide();
        startIntakeFlow();
    }
}

function ajax1(val1) {
    var gen = intakeGeneration; // capture current generation
    $.ajax({
        url: 'addrequest2-ajax1.php?v1=' + val1,
        success: function(result) {
            if (intakeGeneration !== gen) { return; } // stale cascade response
            var c = $('.divservice');
            c.html(result);
            afterCascadeInject(c);
        }
    });
    $('.divsubservice').hide();
    $('.divsubservice2').hide();
    $('.divsubservice3').hide();
}

function ajax2(val1) {
    clearIntakeWorkflow();
    var gen = intakeGeneration;
    $.ajax({
        url: 'addrequest2-ajax2.php?v1=' + val1,
        success: function(result) {
            if (intakeGeneration !== gen) { return; }
            var c = $('.divsubservice');
            c.html(result);
            afterCascadeInject(c);
        }
    });
    $('.divsubservice').show();
    $('.divsubservice2').hide();
    $('.divsubservice3').hide();
}

function ajax3(val1) {
    clearIntakeWorkflow();
    var gen = intakeGeneration;
    $.ajax({
        url: 'addrequest2-ajax3.php?v1=' + val1,
        success: function(result) {
            if (intakeGeneration !== gen) { return; }
            var c = $('.divsubservice2');
            c.html(result);
            afterCascadeInject(c);
        }
    });
    $('.divsubservice2').show();
    $('.divsubservice3').hide();
}

function ajax4(val1) {
    $.ajax({
        url: 'addrequest2-ajax4.php?v1=' + val1,
        success: function(result) { $('.divsubservice3').html(result); }
    });
    $('.divsubservice3').show();
}

// ============================================================================
// Event listeners
// ============================================================================

$(document).ready(function() {

    // Rehydrate in-memory run state from the server-rendered data attributes
    // instead of relying on anything remembered by the browser (e.g. history/
    // back-forward cache). This runs on every full page load, including
    // after a language switch or a plain reload while a run is active.
    var wfEl = document.getElementById('intake-workflow');
    if (wfEl) {
        var dToken = wfEl.dataset.intakeRunToken || '';
        var dCsrf  = wfEl.dataset.intakeCsrf || '';
        var dRev   = parseInt(wfEl.dataset.intakeRevision || '0', 10);
        if (dToken) {
            intakeRunToken    = dToken;
            intakeRunCsrf     = dCsrf;
            intakeRunRevision = isNaN(dRev) ? 0 : dRev;
            intakeShowStartOver(intakeRunToken, intakeRunCsrf);
        }
        hydrateIntakePath(wfEl);

        var validationSummary = wfEl.querySelector('.intake-validation-summary');
        if (validationSummary) { validationSummary.focus(); }
    }

    // Top-level catalogue stream
    $('#service_stream').on('change', function() {
        onStreamChange($(this).val() || '');
    });

    // Question select change — onIntakeAnswerChange itself handles the
    // placeholder by restoring the committed selection and retaining its
    // server-backed downstream branch.
    $(document).on('change', '#intake-workflow .intake-question-select', function() {
        onIntakeAnswerChange(this, this.value);
    });

    // Radio change — same as above.
    $(document).on('change', '#intake-workflow .intake-question-radio', function() {
        onIntakeAnswerChange(this, this.value);
    });

    // Freeform Continue button
    $(document).on('click', '#intake-workflow .intake-ff-continue', function() {
        var nodeContainer = this.closest('.intake-node');
        if (!nodeContainer) { return; }
        var nodeId = nodeContainer.dataset.nodeId;

        // Find the selected option
        var sel    = nodeContainer.querySelector('.intake-question-select');
        var radio  = sel ? null : nodeContainer.querySelector('.intake-question-radio:checked');
        var optionId = sel ? sel.value : (radio ? radio.value : null);
        if (!optionId) { return; }

        // Collect the freeform text for the selected option
        var ffId   = null;
        if (sel) {
            var selOpt = sel.options[sel.selectedIndex];
            ffId = selOpt ? selOpt.dataset.freeformId : null;
        } else if (radio) {
            ffId = radio.dataset.freeformId;
        }
        var ffText = null;
        if (ffId) {
            var ffGroup = document.getElementById(ffId);
            var ta      = ffGroup ? ffGroup.querySelector('textarea') : null;
            ffText = ta ? ta.value.trim() : null;
        }

        submitIntakeAnswer(sel || radio, nodeContainer, nodeId, optionId, ffText);
    });

    $(document).on('input', '#intake-workflow .intake-freeform-text', function() {
        this.setCustomValidity('');
    });

    // PHP fallback forms remain fully functional without JS. Hydration marks
    // them as enhanced and changes their visible submit button to type=button;
    // this handler also catches implicit Enter-key form submission and routes
    // it through the same transactional AJAX answer path.
    $(document).on('submit', '#intake-workflow .intake-nojs-form[data-intake-enhanced="true"]', function(e) {
        e.preventDefault();
        var nodeContainer = this.querySelector('.intake-node');
        if (!nodeContainer) { return; }

        var selection = syncIntakeNodeControls(nodeContainer);
        if (!selection.value) {
            restoreCommittedNodeState(nodeContainer);
            return;
        }

        var freeformText = null;
        if (selection.hasFreeform && selection.freeformId) {
            var group = document.getElementById(selection.freeformId);
            var textarea = group ? group.querySelector('textarea') : null;
            freeformText = textarea ? textarea.value.trim() : null;
        }

        submitIntakeAnswer(
            selection.control,
            nodeContainer,
            nodeContainer.dataset.nodeId,
            selection.value,
            freeformText
        );
    });

    // Retry button (shown after a start error)
    $(document).on('click', '#intake-workflow .intake-retry-btn', function() {
        var wf = document.getElementById('intake-workflow');
        if (wf) { wf.innerHTML = ''; }
        startIntakeFlow();
    });

    // Start Over: when JS is available, intercept the real form submit and
    // clear the workflow in place instead of a full page reload. Without
    // JS this form posts to intake-flow.php (action=restart) normally.
    $(document).on('submit', '#intake-start-over form', function(e) {
        e.preventDefault();
        restartIntakeWorkflow();
    });

    // Language switch: the header renders real <a lang="xx" href="...">
    // links (openrequest.php?lang=xx, preserving other query params via
    // get_language_toggle_url()). When an intake run is active, intercept
    // the click and append the run token before performing a genuine
    // full-page navigation — this is what lets openrequest.php reconstruct
    // the validated session path for that run in the new language. Browser
    // history is never trusted; the run token always comes from the current
    // in-memory state.
    $(document).on('click', '#wb-lng a[lang], .lng-ofr a[lang]', function(e) {
        if (!intakeRunToken) { return; } // no active run — normal navigation
        e.preventDefault();
        var href = this.href;
        var sep  = href.indexOf('?') === -1 ? '?' : '&';
        window.location.href = href + sep + 'run=' + encodeURIComponent(intakeRunToken);
    });
});
