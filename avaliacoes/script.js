document.addEventListener("DOMContentLoaded", () => {
    const apiUrl = "http://localhost/avaliacoes/index.php";
    let dailySubmissionsReviewer = 0; // Avaliador
    let dailySubmissionsCompany = 0;  // Empresa

    // Tela de login e cadastro
    const toggleScreen = (screen) => {
        document.querySelectorAll('.screen').forEach(el => el.classList.add('hidden'));
        document.getElementById(`${screen}-screen`).classList.remove('hidden');
    };

    // Funções gerais de controle de status
    const updateSubmissionStatus = (role) => {
        let statusMessage, submissionCount, maxSubmissions;

        if (role === "company") {
            statusMessage = document.getElementById("company-submission-status");
            submissionCount = dailySubmissionsCompany;
            maxSubmissions = 2;
        } else if (role === "reviewer") {
            statusMessage = document.getElementById("submission-status");
            submissionCount = dailySubmissionsReviewer;
            maxSubmissions = 2;
        }

        if (submissionCount >= maxSubmissions) {
            statusMessage.innerText = "Tentativas diárias esgotadas!";
            document.querySelector(`#${role}-section form`).classList.add('hidden');
        } else {
            statusMessage.innerText = `Você ainda pode enviar ${maxSubmissions - submissionCount} comentário(s) hoje.`;
            document.querySelector(`#${role}-section form`).classList.remove('hidden');
        }
    };

    // Painel da empresa com controle de envios diários
    const loadCompanyPanel = async (user) => {
        document.getElementById('company-section').classList.remove('hidden');

        const resComments = await fetch(`${apiUrl}?route=company_panel&company_id=${user.id}`);
        const comments = await resComments.json();
        const today = new Date().toISOString().split('T')[0];
        dailySubmissionsCompany = comments.filter(comment => comment.review_date.startsWith(today)).length;

        const commentsList = document.getElementById('company-comments');
        commentsList.innerHTML = comments.map(comment => `
            <li>
                <p><strong>${comment.reviewer_name} comentou:</strong> ${comment.comment_content}</p>
                ${comment.screenshot_path ? `<img src="uploads/${comment.screenshot_path}" alt="Print">` : ''}
                <p><em>Data: ${comment.review_date}</em></p>
            </li>
        `).join('');

        updateSubmissionStatus("company");
    };

    // Submissão no painel da empresa
    document.getElementById('post-comment-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const user = JSON.parse(sessionStorage.getItem('user'));
        const commentContent = document.getElementById('company-comment-content').value;

        if (dailySubmissionsCompany >= 2) {
            alert("Tentativas diárias esgotadas!");
            return;
        }

        const res = await fetch(`${apiUrl}?route=post_comment`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ company_id: user.id, content: commentContent })
        });

        if (res.ok) {
            dailySubmissionsCompany += 1;
            alert("Comentário enviado com sucesso!");
            loadCompanyPanel(user);
        } else {
            const data = await res.json();
            alert(data.message);
        }
    });

    // Painel do avaliador com controle de envios diários
    const loadReviewerPanel = async (user) => {
        document.getElementById('reviewer-section').classList.remove('hidden');

        const resHistory = await fetch(`${apiUrl}?route=history&user_id=${user.id}`);
        const history = await resHistory.json();
        const today = new Date().toISOString().split('T')[0];
        dailySubmissionsReviewer = history.filter(review => review.created_at.startsWith(today)).length;

        document.getElementById('reviewer-history').innerHTML = history.map(review => `
            <li>
                <p><strong>${review.reviewer_name} comentou:</strong> ${review.comment_content}</p>
                ${review.screenshot_path ? `<img src="uploads/${review.screenshot_path}" alt="Print">` : ''}
                <p><em>Data: ${review.created_at}</em></p>
            </li>
        `).join('');

        updateSubmissionStatus("reviewer");
    };

    document.getElementById('upload-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const user = JSON.parse(sessionStorage.getItem('user'));
        const commentId = document.getElementById('comment-list').value;
        const screenshot = document.getElementById('screenshot').files[0];
        const commentContent = document.getElementById('reviewer-comment-content').value;

        if (dailySubmissionsReviewer >= 2) {
            alert("Tentativas diárias esgotadas!");
            return;
        }

        const formData = new FormData();
        formData.append('user_id', user.id);
        formData.append('comment_id', commentId);
        formData.append('screenshot', screenshot);

        if (commentContent) {
            await fetch(`${apiUrl}?route=post_comment`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ company_id: commentId, content: commentContent })
            });
        }

        const resUpload = await fetch(`${apiUrl}?route=upload`, {
            method: 'POST',
            body: formData,
        });

        if (resUpload.ok) {
            dailySubmissionsReviewer += 1;
            alert("Envio bem-sucedido!");
            loadReviewerPanel(user);
        } else {
            const data = await resUpload.json();
            alert(data.message);
        }
    });
});
