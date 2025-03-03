function showMessage(elementId, message, isError = false) {
    const messageElement = document.getElementById(elementId);
    messageElement.textContent = message;
    messageElement.className = 'message ' + (isError ? 'error' : 'success');
}

document.getElementById('registerForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/api/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data),
        });
        const result = await response.json();

        if (response.ok) {
            showMessage('registerMessage', 'Registration successful! You can now log in.');
            this.reset(); // Clear the form
        } else {
            let errorMessage = result.message;
            if (result.errors) {
                errorMessage = Object.values(result.errors).flat().join('\n');
            }
            showMessage('registerMessage', errorMessage, true);
        }
    } catch (error) {
        showMessage('registerMessage', 'An error occurred. Please try again later.', true);
        console.error('Error:', error);
    }
});

document.getElementById('loginForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();
        
        console.log('Login response:', result); // Log the response for debugging

        if (response.ok) {
            showMessage('loginMessage', 'Login successful!');
            this.reset(); // Clear the form
            
            // Store user data in local storage
            localStorage.setItem('auth_token', result.data.token);
            localStorage.setItem('user_data', JSON.stringify(result.data.user)); // Store user data
            localStorage.setItem('roles', JSON.stringify(result.data.roles));
            localStorage.setItem('roles_name', JSON.stringify(result.data.rolesName));
            // Redirect to dashboard
            window.location.href = 'dashboard.html';
        } else {
            showMessage('loginMessage', result.message, true);
        }
    } catch (error) {
        showMessage('loginMessage', 'An error occurred. Please try again later.', true);
        console.error('Error:', error);
    }
});

// Function to handle logout
document.getElementById('logoutButton').addEventListener('click', function() {
    // Clear local storage and update UI
    fetch('/api/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}' // Include CSRF token if needed
        },
        credentials: 'include' // Include credentials if using cookies for session
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log(data.message); // Log the success message
        // Clear local storage
        localStorage.clear();
        // Update UI (e.g., redirect to login page or show a message)
        window.location.href = '/login'; // Redirect to login page
    })
    .catch(error => {
        console.error('There was a problem with the logout request:', error);
    });
    showMessage('loginMessage', 'Logged out successfully!');
});
