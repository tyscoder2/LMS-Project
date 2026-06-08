/**
 * Inspects system authentication criteria and prompts for confirmation prior to execution loops.
 */
function handleCatalogActionDispatch(event) {
    if (!window.userIsAuthenticated) {
        event.preventDefault(); // HALT execution lifecycle immediately
        alert("You must sign in first!");
        return false;
    }

    // Identify which action variant sub-element initiated the execution scope
    const activeSubmitter = event.submitter || document.activeElement;
    if (activeSubmitter && activeSubmitter.name === 'book_action') {
        const currentAction = activeSubmitter.value;
        const notificationTerm = (currentAction === 'borrow') ? 'borrow this catalog item' : 'place a reservation queue trace (Watch) for this book';

        if (!confirm(`Are you certain you wish to ${notificationTerm}?`)) {
            event.preventDefault();
            return false;
        }
    }
    return true;
}
