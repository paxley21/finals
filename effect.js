document.addEventListener('DOMContentLoaded', () => {
    // 1. Staggered Animation for Table Rows
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach((row, index) => {
        // Add the CSS class for starting position
        row.classList.add('fade-in-up');
        
        // Delay each row slightly for a "waterfall" effect
        setTimeout(() => {
            row.style.transition = "all 0.6s ease-out";
            row.style.opacity = "1";
            row.style.transform = "translateY(0)";
        }, 100 * index); 
    });

    // 2. Button "Loading" Feedback
    const form = document.querySelector('form');
    const btn = document.querySelector('button[name="add_class"]');

    form.addEventListener('submit', () => {
        btn.innerHTML = "Saving...";
        btn.style.opacity = "0.7";
        btn.style.cursor = "not-allowed";
    });
});

window.addEventListener('load', () => {
    const loader = document.getElementById('loader-wrapper');
    const content = document.getElementById('content');

    // 1. Hide the loader
    loader.style.display = 'none';

    // 2. Show the content and add the fade-in class
    content.style.display = 'block';
    content.classList.add('fade-in');
});

// Add a Search Bar in your HTML above the table first: 
// <input type="text" id="tableSearch" placeholder="Search by Course or Room...">

const searchInput = document.getElementById('tableSearch');
if(searchInput) {
    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('tableSearch');
    
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                // Simple animation: fade out if not matching
                if (text.includes(filter)) {
                    row.style.display = "";
                    row.style.opacity = "1";
                } else {
                    row.style.display = "none";
                    row.style.opacity = "0";
                }
            });
        });
    }
});

function togglePassword() {
    const pass = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    const hint = document.getElementById("eyeHint");

    if (pass.type === "password") {
        pass.type = "text";

        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");

        icon.title = "Hide password";
        hint.textContent = "Hide password";

    } else {
        pass.type = "password";

        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");

        icon.title = "Show password";
        hint.textContent = "Show password";
    }
}

    // <div id="loader-wrapper">
    //     <div class="loader"></div>
    //     <p>Preparing your workspace...</p>
    // </div>

    // <div id="content" style="display:none;">
    //     </div>

    setInterval(() => {
    location.reload();
}, 5000);