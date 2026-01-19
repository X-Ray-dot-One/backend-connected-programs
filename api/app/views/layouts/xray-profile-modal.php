<!-- Profile Setup Modal -->
<div id="profileModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: var(--card); border-radius: 1rem; padding: 2rem; max-width: 500px; width: 90%; border: 1px solid var(--border); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);">
        <h2 style="font-size: 1.5rem; font-weight: bold; color: var(--primary); margin-bottom: 0.5rem;">
            // setup_profile
        </h2>
        <p style="font-size: 0.875rem; color: var(--muted-foreground); margin-bottom: 1.5rem;">
            Choose your username and profile picture
        </p>

        <!-- Error messages -->
        <div id="profileErrors" style="display: none; background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <ul id="profileErrorList" style="margin: 0; padding-left: 1.5rem;"></ul>
        </div>

        <form id="profileForm" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Preview Section -->
            <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 1rem; background: var(--muted); border-radius: 0.5rem;">
                <div id="profilePreview" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; color: white; background: linear-gradient(135deg, var(--primary), var(--accent));">
                    ?
                </div>
                <span id="usernamePreview" style="font-weight: 500; color: var(--muted-foreground);">@username</span>
            </div>

            <!-- Username Input -->
            <div>
                <label for="username" style="display: block; font-weight: 500; color: var(--foreground); margin-bottom: 0.5rem;">
                    Username *
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="yourname"
                    required
                    maxlength="50"
                    style="width: 100%; padding: 0.75rem; background: var(--input); border: 1px solid var(--border); border-radius: 0.5rem; color: var(--foreground); font-family: inherit;">
                <p style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.25rem;">
                    3-50 characters, letters, numbers and underscores only
                </p>
            </div>

            <!-- Profile Picture Input -->
            <div>
                <label for="profile_picture" style="display: block; font-weight: 500; color: var(--foreground); margin-bottom: 0.5rem;">
                    Profile Picture (optional)
                </label>
                <input
                    type="file"
                    id="profile_picture"
                    name="profile_picture"
                    accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                    style="display: none;">
                <button
                    type="button"
                    onclick="document.getElementById('profile_picture').click()"
                    style="width: 100%; padding: 0.75rem; background: var(--muted); border: 1px solid var(--border); border-radius: 0.5rem; color: var(--foreground); font-family: inherit; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s;">
                    <i data-lucide="upload" style="width: 16px; height: 16px;"></i>
                    <span id="uploadButtonText">Choose image</span>
                </button>
                <p style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.25rem;">
                    JPG, PNG, GIF or WebP (max 2MB). Leave empty to use first letter of your username
                </p>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                id="profileSubmitBtn"
                style="padding: 0.75rem; background: var(--primary); color: var(--primary-foreground); border: none; border-radius: 0.75rem; font-weight: 500; cursor: pointer; transition: opacity 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                <span>Save Profile</span>
            </button>
        </form>
    </div>
</div>

<script>
// Profile modal logic
(function() {
    const modal = document.getElementById('profileModal');
    const form = document.getElementById('profileForm');
    const usernameInput = document.getElementById('username');
    const pictureInput = document.getElementById('profile_picture');
    const uploadButtonText = document.getElementById('uploadButtonText');
    const preview = document.getElementById('profilePreview');
    const usernamePreview = document.getElementById('usernamePreview');
    const errorDiv = document.getElementById('profileErrors');
    const errorList = document.getElementById('profileErrorList');
    const submitBtn = document.getElementById('profileSubmitBtn');

    // Update preview on input
    usernameInput.addEventListener('input', function() {
        const username = this.value.trim();
        if (username) {
            usernamePreview.textContent = '@' + username;
            if (!pictureInput.files || !pictureInput.files[0]) {
                preview.textContent = username.charAt(0).toUpperCase();
            }
        } else {
            usernamePreview.textContent = '@username';
            if (!pictureInput.files || !pictureInput.files[0]) {
                preview.textContent = '?';
            }
        }
    });

    // Handle file selection
    pictureInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            // Check file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('File is too large. Maximum size is 2MB.');
                this.value = '';
                uploadButtonText.textContent = 'Choose image';
                return;
            }

            // Update button text
            uploadButtonText.textContent = file.name;

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            };
            reader.readAsDataURL(file);
        } else {
            uploadButtonText.textContent = 'Choose image';
            const username = usernameInput.value.trim();
            preview.textContent = username ? username.charAt(0).toUpperCase() : '?';
        }
    });

    // Show modal function
    window.showProfileModal = function() {
        modal.style.display = 'flex';
    };

    // Hide modal function
    window.hideProfileModal = function() {
        modal.style.display = 'none';
    };

    // Handle form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const username = usernameInput.value.trim();

        // Disable button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span><span>Saving...</span>';

        try {
            // Use FormData to send file
            const formData = new FormData();
            formData.append('username', username);

            if (pictureInput.files && pictureInput.files[0]) {
                formData.append('profile_picture', pictureInput.files[0]);
            }

            const response = await fetch('index.php?action=update-profile', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Reload page to show updated profile
                window.location.reload();
            } else {
                // Show errors
                errorDiv.style.display = 'block';
                errorList.innerHTML = '';
                (data.errors || ['An error occurred']).forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorList.appendChild(li);
                });

                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="check" style="width: 16px; height: 16px;"></i><span>Save Profile</span>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            alert('Failed to update profile. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i data-lucide="check" style="width: 16px; height: 16px;"></i><span>Save Profile</span>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });
})();
</script>
