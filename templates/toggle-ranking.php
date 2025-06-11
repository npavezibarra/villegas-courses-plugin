<!-- Toggle for hiding the score -->
<div class="toggle" style="display: flex; align-items: center; gap: 10px;">
        <label class="switch" style="position: relative; display: inline-block; width: 50px; height: 26px;">
            <input type="checkbox" id="ocultar-puntaje" onclick="toggleScore()">
            <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: 0.4s; border-radius: 50px;"></span>
            <span class="slider round" style="position: absolute; content: ''; height: 16px; width: 16px; border-radius: 50px; left: 5px; bottom: 5px; background-color: white; transition: 0.4s;"></span>
        </label>
        <span>Ocultar Puntaje</span>
</div>

<script>
    function toggleScore() {
        var scoreButton = document.querySelector('.wpProQuiz_button_restartQuiz');
        var toggle = document.getElementById('ocultar-puntaje');

        // Check if the button exists to avoid errors
        if (scoreButton) {
            if (toggle.checked) {
                scoreButton.style.display = 'none';  // Hide the score
            } else {
                scoreButton.style.display = 'block'; // Show the score
            }
        }

        // Change the background color of the switch when active (green)
        if (toggle.checked) {
            toggle.nextElementSibling.style.backgroundColor = '#4caf50';  // Green
            toggle.nextElementSibling.nextElementSibling.style.transform = 'translateX(24px)';
        } else {
            toggle.nextElementSibling.style.backgroundColor = '#ccc';  // Gray
            toggle.nextElementSibling.nextElementSibling.style.transform = 'translateX(0)';
        }
    }
</script>