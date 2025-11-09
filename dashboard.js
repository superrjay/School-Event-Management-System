// Tab Navigation
function showTab(tabName) {
  const tabs = document.querySelectorAll(".tab-content");
  tabs.forEach((tab) => tab.classList.remove("active"));

  const links = document.querySelectorAll(".sidebar a");
  links.forEach((link) => link.classList.remove("active"));

  document.getElementById(tabName).classList.add("active");
  event.target.classList.add("active");

  // Load data when tab is opened
  if (tabName === "overview") loadOverview();
  else if (tabName === "profile") loadProfile();
  else if (tabName === "events") loadEvents();
  else if (tabName === "browse-events") loadBrowseEvents();
  else if (tabName === "my-registrations") loadMyRegistrations();
  else if (tabName === "attendance") loadAttendanceSection();
  else if (tabName === "budget") loadBudgetSection();
  else if (tabName === "feedback") loadFeedbackSection();
  else if (tabName === "users") loadUsers();
}

// Show/Hide Forms
function showEventForm() {
  document.getElementById("event-form").style.display = "block";
}

function hideEventForm() {
  document.getElementById("event-form").style.display = "none";
  document.getElementById("eventForm").reset();
  document.getElementById("event_id").value = "";
}

function showActivityFlow() {
  const eventId = document.getElementById("events-event-select")?.value;
  if (!eventId) {
    alert("Please select an event first");
    return;
  }
  
  document.getElementById("activity_event_id").value = eventId;
  document.getElementById("activity-flow").style.display = "block";
  loadActivityFlow(eventId);
}

function hideActivityForm() {
  document.getElementById("activity-flow").style.display = "none";
  document.getElementById("activityForm").reset();
}

function showBudgetForm() {
  const eventId = document.getElementById("budget-event-select").value;
  if (!eventId) {
    alert("Please select an event first");
    return;
  }
  document.getElementById("budget_event_id").value = eventId;
  document.getElementById("budget-form").style.display = "block";
}

function hideBudgetForm() {
  document.getElementById("budget-form").style.display = "none";
  document.getElementById("budgetForm").reset();
  document.getElementById("budget_id").value = "";
}

function showUserForm() {
  document.getElementById("user-form").style.display = "block";
}

function hideUserForm() {
  document.getElementById("user-form").style.display = "none";
  document.getElementById("userForm").reset();
  document.getElementById("user_id").value = "";
}

// Message Display
function showMessage(message, type = "success") {
  const container = document.getElementById("message-container");
  container.innerHTML = `<div class="${type}">${message}</div>`;
  setTimeout(() => {
    container.innerHTML = "";
  }, 3000);
}

// Load Overview Stats
function loadOverview() {
  fetch("api.php?action=overview")
    .then((res) => res.json())
    .then((data) => {
      let html = '<div class="stats-grid">';
      html += `<div class="stat-card">
                <div class="icon"><i class="fas fa-calendar"></i></div>
                <h3>Total Events</h3>
                <div class="number">${data.total_events}</div>
              </div>`;
      html += `<div class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h3>Upcoming Events</h3>
                <div class="number">${data.upcoming_events}</div>
              </div>`;
      html += `<div class="stat-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3>My Registrations</h3>
                <div class="number">${data.my_registrations}</div>
              </div>`;
      html += `<div class="stat-card">
                <div class="icon"><i class="fas fa-user-friends"></i></div>
                <h3>Total Users</h3>
                <div class="number">${data.total_users}</div>
              </div>`;
      html += '</div>';
      document.getElementById("overview-stats").innerHTML = html;
    })
    .catch(error => {
      console.error('Error loading overview:', error);
      showMessage('Error loading dashboard data', 'error');
    });
}

// Profile Management
function loadProfile() {
  // Profile data is already loaded in PHP
}

function updateProfile() {
  const formData = new FormData();
  formData.append('action', 'update_profile');
  formData.append('full_name', document.getElementById('profile_full_name').value);
  formData.append('email', document.getElementById('profile_email').value);

  fetch('api.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    showMessage(data.message, data.success ? 'success' : 'error');
  })
  .catch(error => {
    console.error('Error updating profile:', error);
    showMessage('Error updating profile', 'error');
  });
}

// Activity Flow Functions
function loadActivityFlow(eventId) {
  fetch(`api.php?action=get_activity_flow&event_id=${eventId}`)
    .then(res => res.json())
    .then(data => {
      let html = '<h4>Activity List</h4>';
      if (data.length === 0) {
        html += '<p>No activities scheduled.</p>';
      } else {
        html += '<table><tr><th>Description</th><th>Start Time</th><th>End Time</th><th>Action</th></tr>';
        data.forEach(activity => {
          html += `<tr>
            <td>${activity.description}</td>
            <td>${activity.start_time}</td>
            <td>${activity.end_time}</td>
            <td><button onclick="deleteActivity(${activity.id})" class="btn-danger">Delete</button></td>
          </tr>`;
        });
        html += '</table>';
      }
      document.getElementById("activity-list").innerHTML = html;
    })
    .catch(error => {
      console.error('Error loading activities:', error);
      showMessage('Error loading activities', 'error');
    });
}

// Activity Form Submission
document.getElementById("activityForm")?.addEventListener("submit", function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append("action", "create_activity");
  
  fetch("api.php", { method: "POST", body: formData })
    .then(res => res.json())
    .then(data => {
      showMessage(data.message, data.success ? "success" : "error");
      if (data.success) {
        this.reset();
        const eventId = document.getElementById("activity_event_id").value;
        loadActivityFlow(eventId);
      }
    })
    .catch(error => {
      console.error('Error saving activity:', error);
      showMessage('Error saving activity', 'error');
    });
});

function deleteActivity(id) {
  if (!confirm("Delete this activity?")) return;
  
  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=delete_activity&id=${id}`,
  })
  .then(res => res.json())
  .then(data => {
    showMessage(data.message, data.success ? "success" : "error");
    if (data.success) {
      const eventId = document.getElementById("activity_event_id").value;
      loadActivityFlow(eventId);
    }
  })
  .catch(error => {
    console.error('Error deleting activity:', error);
    showMessage('Error deleting activity', 'error');
  });
}

// Browse Events (Student/Guest)
function loadBrowseEvents() {
  fetch("api.php?action=browse_events")
    .then((res) => res.json())
    .then((data) => {
      let html = `
        <table>
          <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Venue</th>
            <th>Capacity</th>
            <th>Registered</th>
            <th>Action</th>
          </tr>
      `;
      data.forEach((event) => {
        const isRegistered = event.user_registered == 1;
        const startDate = event.start_datetime ? new Date(event.start_datetime).toLocaleString() : event.event_date + ' ' + event.event_time;
        const endDate = event.end_datetime ? new Date(event.end_datetime).toLocaleString() : 'N/A';
        
        html += `<tr>
          <td>${event.title}</td>
          <td>${event.description || "N/A"}</td>
          <td>${startDate}</td>
          <td>${endDate}</td>
          <td>${event.venue || "N/A"}</td>
          <td>${event.capacity}</td>
          <td>${event.registration_count || 0}</td>
          <td>
            ${isRegistered
              ? `<button onclick="unregisterEvent(${event.id})" class="btn-danger">Unregister</button>`
              : `<button onclick="registerEvent(${event.id})" class="btn-success">Register</button>`
            }
          </td>
        </tr>`;
      });
      html += "</table>";
      document.getElementById("browse-events-list").innerHTML = html;
    })
    .catch(error => {
      console.error('Error loading browse events:', error);
      showMessage('Error loading events', 'error');
    });
}

function registerEvent(eventId) {
  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=register_event&event_id=${eventId}`,
  })
    .then((res) => res.json())
    .then((data) => {
      showMessage(data.message, data.success ? "success" : "error");
      if (data.success) {
        loadBrowseEvents();
        loadMyRegistrations();
      }
    })
    .catch(error => {
      console.error('Error registering for event:', error);
      showMessage('Error registering for event', 'error');
    });
}

function unregisterEvent(eventId, userId = null) {
  if (!confirm("Are you sure you want to unregister from this event?")) return;
  
  const formData = new FormData();
  formData.append('action', 'unregister_event');
  formData.append('event_id', eventId);
  if (userId) {
    formData.append('user_id', userId);
  }
  
  fetch('api.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    showMessage(data.message, data.success ? 'success' : 'error');
    if (data.success) {
      loadBrowseEvents();
      loadMyRegistrations();
    }
  })
  .catch(error => {
    console.error('Error unregistering from event:', error);
    showMessage('Error unregistering from event', 'error');
  });
}

// My Registrations
function loadMyRegistrations() {
  fetch("api.php?action=my_registrations")
    .then((res) => res.json())
    .then((data) => {
      let html = `
        <table>
          <tr>
            <th>Event</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Venue</th>
            <th>Status</th>
            <th>Registered On</th>
            <th>Action</th>
          </tr>
      `;
      data.forEach((reg) => {
        const startDate = reg.start_datetime ? new Date(reg.start_datetime).toLocaleString() : reg.event_date + ' ' + reg.event_time;
        const endDate = reg.end_datetime ? new Date(reg.end_datetime).toLocaleString() : 'N/A';
        
        html += `<tr>
          <td>${reg.event_title}</td>
          <td>${startDate}</td>
          <td>${endDate}</td>
          <td>${reg.venue || "N/A"}</td>
          <td>${reg.event_status}</td>
          <td>${new Date(reg.registration_date).toLocaleDateString()}</td>
          <td>
            <button onclick="unregisterEvent(${reg.event_id})" class="btn-danger">Cancel</button>
            ${reg.event_status === 'completed' ? 
              `<button onclick="showFeedbackForm(${reg.event_id})" class="btn-primary">Give Feedback</button>` : 
              ''
            }
          </td>
        </tr>`;
      });
      html += "</table>";
      document.getElementById("my-registrations-list").innerHTML = html;
    })
    .catch(error => {
      console.error('Error loading registrations:', error);
      showMessage('Error loading registrations', 'error');
    });
}

// Feedback Section
function loadFeedbackSection() {
  loadEventsForDropdown("feedback-event-select");
  loadEventsForDropdown("view-feedback-event-select");

  const select = document.getElementById("feedback-event-select");
  if (select) {
    select.addEventListener("change", function () {
      const container = document.getElementById("feedback-form-container");
      if (this.value) {
        document.getElementById("feedback_event_id").value = this.value;
        container.style.display = "block";
      } else {
        container.style.display = "none";
      }
    });
  }
}

document.getElementById("feedbackForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append("action", "submit_feedback");

  fetch("api.php", { method: "POST", body: formData })
    .then((res) => res.json())
    .then((data) => {
      showMessage(data.message, data.success ? "success" : "error");
      if (data.success) {
        this.reset();
        document.getElementById("feedback-form-container").style.display = "none";
        document.getElementById("feedback-event-select").value = "";
      }
    })
    .catch(error => {
      console.error('Error submitting feedback:', error);
      showMessage('Error submitting feedback', 'error');
    });
});

function loadFeedbackList() {
  const eventId = document.getElementById("view-feedback-event-select").value;
  if (!eventId) {
    document.getElementById("feedback-list").innerHTML = "";
    return;
  }

  fetch(`api.php?action=get_feedback&event_id=${eventId}`)
    .then((res) => res.json())
    .then((data) => {
      let html = "<h3>Feedback Summary</h3>";
      if (data.length === 0) {
        html += "<p>No feedback available for this event.</p>";
      } else {
        html += '<table><tr><th>Participant</th><th>Rating</th><th>Comment</th><th>Date</th></tr>';
        let totalRating = 0;
        data.forEach((fb) => {
          totalRating += parseInt(fb.rating);
          html += `<tr>
            <td>${fb.full_name}</td>
            <td>${'★'.repeat(fb.rating)}${'☆'.repeat(5-fb.rating)} (${fb.rating}/5)</td>
            <td>${fb.comment || "No comment"}</td>
            <td>${new Date(fb.submitted_at).toLocaleDateString()}</td>
          </tr>`;
        });
        const avgRating = (totalRating / data.length).toFixed(2);
        html += `<tr><td colspan="4" style="text-align: center; font-weight: bold;">
                  Average Rating: ${avgRating} / 5 (${data.length} responses)
                </td></tr>`;
        html += "</table>";
      }
      document.getElementById("feedback-list").innerHTML = html;
    })
    .catch(error => {
      console.error('Error loading feedback:', error);
      showMessage('Error loading feedback', 'error');
    });
}

function showFeedbackForm(eventId) {
  showTab('feedback');
  setTimeout(() => {
    document.getElementById('feedback-event-select').value = eventId;
    document.getElementById('feedback_event_id').value = eventId;
    document.getElementById('feedback-form-container').style.display = 'block';
  }, 100);
}

// User Management
function loadUsers() {
  fetch("api.php?action=get_users")
    .then((res) => res.json())
    .then((data) => {
      let html = `
        <table>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Full Name</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
      `;
      data.forEach((user) => {
        html += `<tr>
          <td>${user.id}</td>
          <td>${user.username}</td>
          <td>${user.email}</td>
          <td>${user.full_name}</td>
          <td>${user.role}</td>
          <td>
            <button onclick="editUser(${user.id})" class="btn-primary">Edit</button>
            <button onclick="deleteUser(${user.id})" class="btn-danger">Delete</button>
          </td>
        </tr>`;
      });
      html += "</table>";
      document.getElementById("users-list").innerHTML = html;
    })
    .catch(error => {
      console.error('Error loading users:', error);
      showMessage('Error loading users', 'error');
    });
}

document.getElementById("userForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append(
    "action",
    document.getElementById("user_id").value ? "update_user" : "create_user"
  );

  fetch("api.php", { method: "POST", body: formData })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        showMessage(data.message);
        hideUserForm();
        loadUsers();
      } else {
        showMessage(data.message, "error");
      }
    })
    .catch(error => {
      console.error('Error saving user:', error);
      showMessage('Error saving user', 'error');
    });
});

function editUser(id) {
  fetch(`api.php?action=get_user&id=${id}`)
    .then((res) => res.json())
    .then((user) => {
      document.getElementById("user_id").value = user.id;
      document.getElementById("user_username").value = user.username;
      document.getElementById("user_email").value = user.email;
      document.getElementById("user_full_name").value = user.full_name;
      document.getElementById("user_role").value = user.role;
      document.getElementById("user_password").value = '';
      showUserForm();
    })
    .catch(error => {
      console.error('Error loading user:', error);
      showMessage('Error loading user details', 'error');
    });
}

function deleteUser(id) {
  if (!confirm("Delete this user?")) return;

  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=delete_user&id=${id}`,
  })
    .then((res) => res.json())
    .then((data) => {
      showMessage(data.message, data.success ? "success" : "error");
      if (data.success) loadUsers();
    })
    .catch(error => {
      console.error('Error deleting user:', error);
      showMessage('Error deleting user', 'error');
    });
}

// Helper: Load events for dropdowns
function loadEventsForDropdown(selectId) {
  fetch("api.php?action=get_all_events")
    .then((res) => res.json())
    .then((data) => {
      const select = document.getElementById(selectId);
      if (!select) return;

      select.innerHTML = '<option value="">-- Select Event --</option>';
      data.forEach((event) => {
        select.innerHTML += `<option value="${event.id}">${event.title} (${event.event_date})</option>`;
      });
    })
    .catch(error => {
      console.error('Error loading events for dropdown:', error);
    });
}

// Automated Status Update
function updateEventStatuses() {
  fetch("api.php?action=update_event_statuses")
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        console.log('Event statuses updated:', data.message);
      }
    })
    .catch(error => {
      console.error('Error updating event statuses:', error);
    });
}

// Run status update every 5 minutes
setInterval(updateEventStatuses, 300000);

// Initialize on page load
window.addEventListener("DOMContentLoaded", function () {
  // Load appropriate default tab based on role
  const activeTab = document.querySelector('.tab-content.active');
  if (activeTab) {
    const tabId = activeTab.id;
    if (tabId === 'overview') loadOverview();
    else if (tabId === 'profile') loadProfile();
    else if (tabId === 'events') loadEvents();
    else if (tabId === 'browse-events') loadBrowseEvents();
    else if (tabId === 'my-registrations') loadMyRegistrations();
  }

  // Load dropdowns
  loadEventsForDropdown("attendance-event-select");
  loadEventsForDropdown("budget-event-select");
});