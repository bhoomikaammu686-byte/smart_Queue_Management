const loggedInUser = sessionStorage.getItem("smartQueueDemoUser");

if (!loggedInUser) {
    window.location.replace("login.html");
}

const API = "api.php";
let queue = [];
let pendingDeleteId = null;

const $ = (id) => document.getElementById(id);

async function request(action, data = null) {
    const options = data !== null
        ? {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        }
        : {};

    const response = await fetch(
        `${API}?action=${encodeURIComponent(action)}`,
        options
    );

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error("Invalid server response. Check PHP errors.");
    }

    if (!response.ok || result.error) {
        throw new Error(result.error || "Request failed.");
    }

    return result;
}

function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, char => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[char]);
}

function updateStats() {
    $("total").textContent = queue.length;
    $("waiting").textContent =
        queue.filter(t => t.status === "Waiting").length;
    $("serving").textContent =
        queue.filter(t => t.status === "Serving").length;
    $("completed").textContent =
        queue.filter(t => t.status === "Completed").length;
    $("documents").textContent =
        queue.filter(t => t.service === "Document Verification").length;
    $("support").textContent =
        queue.filter(t => t.service === "Customer Support").length;
}

function renderQueue() {
    const searchInput = $("search");
    const queueBody = $("queueBody");

    if (!queueBody) return;

    const term = searchInput
        ? searchInput.value.trim().toLowerCase()
        : "";

    const filtered = queue.filter(token =>
        [
            token.token_no,
            token.customer_name,
            token.service,
            token.status,
            token.created_at
        ].some(value =>
            String(value ?? "").toLowerCase().includes(term)
        )
    );

    if (filtered.length === 0) {
        queueBody.innerHTML =
            '<tr><td colspan="6">No queue tokens found.</td></tr>';
    } else {
        queueBody.innerHTML = filtered.map(token => {
            let action = "—";

            if (token.status === "Waiting") {
                action = `
                    <button class="queue-action call-action"
                        data-action="call"
                        data-id="${Number(token.id)}">
                        Call Token
                    </button>`;
            } else if (token.status === "Serving") {
                action = `
                    <button class="queue-action complete-action"
                        data-action="complete"
                        data-id="${Number(token.id)}">
                        Complete
                    </button>`;
            }

            const deleteButton = `
                <button class="queue-action delete-action"
                    data-action="delete"
                    data-id="${Number(token.id)}"
                    style="background:#fff0f0;color:#c62828;border:1px solid #ffd6d6;margin-left:5px;">
                    Delete
                </button>`;

            return `
                <tr>
                    <td class="token">${escapeHTML(token.token_no)}</td>
                    <td>${escapeHTML(token.customer_name)}</td>
                    <td>${escapeHTML(token.service)}</td>
                    <td>${escapeHTML(token.created_at)}</td>
                    <td>
                        <span class="status ${escapeHTML(token.status)}">
                            ${escapeHTML(token.status)}
                        </span>
                    </td>
                    <td>${action} ${deleteButton}</td>
                </tr>`;
        }).join("");
    }

    const count = $("count");

    if (count) {
        count.textContent =
            `${filtered.length} token${filtered.length === 1 ? "" : "s"}`;
    }

    updateStats();
}

async function loadQueue() {
    const refreshButton = $("refreshBtn");
    const queueBody = $("queueBody");

    if (refreshButton) refreshButton.disabled = true;

    try {
        const result = await request("list");

        if (!Array.isArray(result)) {
            throw new Error(result.error || "Invalid queue data.");
        }

        queue = result;
        renderQueue();
    } catch (error) {
        if (queueBody) {
            queueBody.innerHTML =
                `<tr><td colspan="6">${escapeHTML(error.message)}</td></tr>`;
        }

        console.error("Queue loading error:", error);
    } finally {
        if (refreshButton) refreshButton.disabled = false;
    }
}

// Generate a token
const tokenForm = $("tokenForm");

if (tokenForm) {
    tokenForm.addEventListener("submit", async event => {
        event.preventDefault();

        const message = $("message");
        const submitButton =
            tokenForm.querySelector('button[type="submit"]');

        const data = {
            customer_name: $("customer_name")?.value.trim() || "",
            service: $("service")?.value || ""
        };

        if (!data.customer_name || !data.service) {
            if (message) {
                message.textContent =
                    "Enter customer name and select a service.";
            }
            return;
        }

        if (submitButton) submitButton.disabled = true;
        if (message) message.textContent = "";

        try {
            const result = await request("add", data);

            if (message) {
                message.textContent =
                    `${result.message} Token: ${result.token_no}`;
            }

            tokenForm.reset();
            await loadQueue();
        } catch (error) {
            if (message) message.textContent = error.message;
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    });
}

// Open the custom delete popup
function openDeleteModal(id) {
    const token = queue.find(item => Number(item.id) === id);
    const modal = $("deleteModal");

    if (!modal || !token) {
        alert("Unable to find this queue token.");
        return;
    }

    pendingDeleteId = id;

    $("deleteModalMessage").textContent =
        `Are you sure you want to permanently delete token ${token.token_no} for ${token.customer_name}? This action cannot be undone.`;

    $("confirmDeleteBtn").disabled = false;
    $("confirmDeleteBtn").textContent = "Delete Token";

    modal.classList.add("show");
    modal.setAttribute("aria-hidden", "false");
}

// Close the custom delete popup
function closeDeleteModal() {
    const modal = $("deleteModal");

    if (!modal || $("confirmDeleteBtn").disabled) return;

    modal.classList.remove("show");
    modal.setAttribute("aria-hidden", "true");
    pendingDeleteId = null;
}

const cancelDeleteBtn = $("cancelDeleteBtn");
const deleteModalOverlay = $("deleteModalOverlay");

if (cancelDeleteBtn) {
    cancelDeleteBtn.addEventListener("click", closeDeleteModal);
}

if (deleteModalOverlay) {
    deleteModalOverlay.addEventListener("click", closeDeleteModal);
}

// Confirm deletion
const confirmDeleteBtn = $("confirmDeleteBtn");

if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", async () => {
        if (pendingDeleteId === null) return;

        const id = pendingDeleteId;

        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.textContent = "Deleting...";

        try {
            const result = await request("delete", { id });

            const modal = $("deleteModal");
            modal.classList.remove("show");
            modal.setAttribute("aria-hidden", "true");

            pendingDeleteId = null;

            alert(result.message || "Token deleted successfully.");
            await loadQueue();
        } catch (error) {
            alert(error.message);
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.textContent = "Delete Token";
        }
    });
}

// Handle Call and Complete buttons, and open Delete popup
const queueBody = $("queueBody");

if (queueBody) {
    queueBody.addEventListener("click", async event => {
        const button = event.target.closest("button[data-action]");

        if (!button) return;

        const action = button.dataset.action;
        const id = Number(button.dataset.id);

        if (!id) return;

        if (action === "delete") {
            openDeleteModal(id);
            return;
        }

        if (!["call", "complete"].includes(action)) return;

        button.disabled = true;

        try {
            let result;

            if (action === "call") {
                result = await request("call_next", {});
            } else {
                result = await request("complete", { id });
            }

            alert(result.message || "Queue updated successfully.");
            await loadQueue();
        } catch (error) {
            alert(error.message);
            await loadQueue();
        }
    });
}

// Refresh queue
const refreshButton = $("refreshBtn");

if (refreshButton) {
    refreshButton.addEventListener("click", loadQueue);
}

// Search queue
const searchInput = $("search");

if (searchInput) {
    searchInput.addEventListener("input", renderQueue);
}

// Load queue on opening the dashboard
loadQueue();