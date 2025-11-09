document.addEventListener('DOMContentLoaded', () => {
  const style = document.createElement('style');
  style.innerHTML = `
    #quiz-card h2 {
      font-family: 'Arial', sans-serif;
      font-size: 24px;
      text-align: center;
      margin-bottom: 15px;
      margin-top: 4px;
      font-weight: 600;
    }

    #quiz-card h3 {
      font-family: sans-serif;
      font-size: 25px;
      text-align: center;
      margin-bottom: 6px;
    }

    #quiz-card h4 {
      font-family: sans-serif;
      font-size: 25px;
    }

    #quiz-card a.wpProQuiz_pointsChart__cta {
      font-size: 14px;
    }

    #quiz-card input.wpProQuiz_button {
      font-size: 14px !important;
    }

    #quiz-card a.back-to-course-link {
      color: #2890e8;
      font-size: 12px;
      margin: auto;
      border: 1px solid #2890e8;
      max-width: 134px;
      margin-top: 20px;
      border-radius: 3px;
      padding: 5px;
      display: block;
      text-align: center;
    }

    #quiz-card .learndash-wrapper a {
      box-shadow: none !important;
      text-decoration: none;
      text-shadow: none;
    }
  `;
  document.head.appendChild(style);
});
