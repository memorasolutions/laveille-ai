/**
 * Tirage au Sort - Présentations
 * Application JavaScript modulaire
 */

// ==================== VARIABLES GLOBALES ====================
let currentStudents = [];
let currentQuestions = [];
let drawHistory = [];

// ==================== INITIALISATION ====================
document.addEventListener('DOMContentLoaded', function() {
    loadData();
    updateCounts();
    updateRemainingCounts();
    renderHistory();
});

// ==================== GESTION DES ONGLETS ====================
function switchTab(tabName) {
    // Cacher tous les onglets
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Désactiver tous les boutons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });

    // Activer l'onglet et le bouton sélectionnés
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');

    // Mettre à jour les compteurs
    if (tabName === 'draw') {
        updateRemainingCounts();
    }
}

// ==================== SAUVEGARDE/CHARGEMENT DES DONNÉES ====================
function saveData() {
    const data = {
        students: document.getElementById('students-list').value,
        questions: document.getElementById('questions-list').value,
        removeStudent: document.getElementById('option-remove-student').checked,
        removeQuestion: document.getElementById('option-remove-question').checked,
        currentStudents: currentStudents,
        currentQuestions: currentQuestions,
        history: drawHistory
    };

    localStorage.setItem('tirage-presentations', JSON.stringify(data));
}

function loadData() {
    const saved = localStorage.getItem('tirage-presentations');
    if (saved) {
        try {
            const data = JSON.parse(saved);
            document.getElementById('students-list').value = data.students || '';
            document.getElementById('questions-list').value = data.questions || '';
            document.getElementById('option-remove-student').checked = data.removeStudent !== false;
            document.getElementById('option-remove-question').checked = data.removeQuestion !== false;
            currentStudents = data.currentStudents || [];
            currentQuestions = data.currentQuestions || [];
            drawHistory = data.history || [];
        } catch (e) {
            console.error('Erreur lors du chargement des données:', e);
        }
    }
}

function clearAllData() {
    if (confirm('Êtes-vous sûr de vouloir effacer toutes les données?\n\nCela supprimera:\n- Les listes d\'apprenants et de questions\n- L\'historique des tirages\n- Toutes les données sauvegardées\n\nCette action est irréversible.')) {
        localStorage.removeItem('tirage-presentations');
        document.getElementById('students-list').value = '';
        document.getElementById('questions-list').value = '';
        currentStudents = [];
        currentQuestions = [];
        drawHistory = [];
        updateCounts();
        updateRemainingCounts();
        renderHistory();
        showAlert('success', 'Toutes les données ont été effacées avec succès.', 'config');
    }
}

// ==================== GESTION DES COMPTEURS ====================
function updateCounts() {
    const studentsText = document.getElementById('students-list').value.trim();
    const questionsText = document.getElementById('questions-list').value.trim();

    const studentsCount = studentsText ? studentsText.split('\n').filter(s => s.trim()).length : 0;
    const questionsCount = questionsText ? questionsText.split('\n').filter(q => q.trim()).length : 0;

    document.getElementById('students-count').textContent = studentsCount;
    document.getElementById('questions-count').textContent = questionsCount;
}

function updateRemainingCounts() {
    // Si les tableaux actuels sont vides, utiliser les listes de l'onglet Configuration
    let studentsCount = currentStudents.length;
    let questionsCount = currentQuestions.length;

    if (studentsCount === 0) {
        const studentsText = document.getElementById('students-list').value.trim();
        studentsCount = studentsText ? studentsText.split('\n').filter(s => s.trim()).length : 0;
    }

    if (questionsCount === 0) {
        const questionsText = document.getElementById('questions-list').value.trim();
        questionsCount = questionsText ? questionsText.split('\n').filter(q => q.trim()).length : 0;
    }

    document.getElementById('students-remaining').textContent = studentsCount;
    document.getElementById('questions-remaining').textContent = questionsCount;
}

// ==================== TIRAGE AU SORT ====================
function performDraw() {
    // Récupérer les listes
    const studentsText = document.getElementById('students-list').value.trim();
    const questionsText = document.getElementById('questions-list').value.trim();

    // Initialiser les listes actuelles si vides
    if (currentStudents.length === 0) {
        currentStudents = studentsText.split('\n').filter(s => s.trim());
    }
    if (currentQuestions.length === 0) {
        currentQuestions = questionsText.split('\n').filter(q => q.trim());
    }

    // Vérifications
    if (currentStudents.length === 0) {
        showAlert('error', 'Aucun apprenant disponible. Retournez à l\'onglet Configuration pour ajouter des apprenants.', 'draw');
        return;
    }

    if (currentQuestions.length === 0) {
        showAlert('error', 'Aucune question disponible. Retournez à l\'onglet Configuration pour ajouter des questions.', 'draw');
        return;
    }

    // Tirage aléatoire
    const studentIndex = Math.floor(Math.random() * currentStudents.length);
    const questionIndex = Math.floor(Math.random() * currentQuestions.length);

    const selectedStudent = currentStudents[studentIndex];
    const selectedQuestion = currentQuestions[questionIndex];

    // Afficher le résultat avec animation
    displayResult(selectedStudent, selectedQuestion);

    // Ajouter à l'historique
    drawHistory.push({
        student: selectedStudent,
        question: selectedQuestion,
        timestamp: new Date().toISOString()
    });

    // Retirer si options activées
    if (document.getElementById('option-remove-student').checked) {
        currentStudents.splice(studentIndex, 1);
    }
    if (document.getElementById('option-remove-question').checked) {
        currentQuestions.splice(questionIndex, 1);
    }

    // Sauvegarder et mettre à jour
    saveData();
    updateRemainingCounts();
    renderHistory();

    // Vérifier s'il reste des éléments
    if (currentStudents.length === 0 || currentQuestions.length === 0) {
        setTimeout(() => {
            const msg = currentStudents.length === 0
                ? 'Tous les apprenants ont été tirés au sort!'
                : 'Toutes les questions ont été utilisées!';
            showAlert('success', msg + ' Vous pouvez réinitialiser les listes dans l\'onglet Configuration.', 'draw');
        }, 2000);
    }
}

function displayResult(student, question) {
    const resultDiv = document.getElementById('draw-result');
    resultDiv.classList.remove('empty');

    // Effacer les animations précédentes
    const existingStudent = resultDiv.querySelector('.student-name');
    const existingQuestion = resultDiv.querySelector('.question-text');
    if (existingStudent) existingStudent.remove();
    if (existingQuestion) existingQuestion.remove();

    // Créer les éléments
    const studentEl = document.createElement('div');
    studentEl.className = 'student-name';
    studentEl.textContent = student;

    const questionEl = document.createElement('div');
    questionEl.className = 'question-text';
    questionEl.textContent = question;

    // Ajouter au DOM
    resultDiv.innerHTML = '';
    resultDiv.appendChild(studentEl);
    resultDiv.appendChild(questionEl);

    // Déclencher les animations
    setTimeout(() => studentEl.classList.add('show'), 50);
    setTimeout(() => questionEl.classList.add('show'), 100);
}

// ==================== HISTORIQUE ====================
function renderHistory() {
    const historyList = document.getElementById('history-list');

    if (drawHistory.length === 0) {
        historyList.innerHTML = '<li class="history-empty">Aucun tirage effectué pour le moment</li>';
        return;
    }

    historyList.innerHTML = '';
    drawHistory.forEach((item, index) => {
        const li = document.createElement('li');
        li.className = 'history-item';
        li.innerHTML = `
            <div class="history-number">#${index + 1}</div>
            <div class="history-student">${escapeHtml(item.student)}</div>
            <div class="history-question">${escapeHtml(item.question)}</div>
        `;
        historyList.appendChild(li);
    });
}

function clearHistory() {
    if (confirm('Voulez-vous vraiment effacer l\'historique des tirages?')) {
        drawHistory = [];
        renderHistory();
        saveData();
        showAlert('success', 'Historique effacé avec succès.', 'draw');
    }
}

// ==================== RÉINITIALISATION ====================
function resetAll() {
    if (confirm('Voulez-vous réinitialiser les listes de tirage?\n\nCela remettra tous les apprenants et questions disponibles pour un nouveau cycle de tirage.')) {
        currentStudents = [];
        currentQuestions = [];

        // Réinitialiser l'affichage du résultat dans l'onglet Tirage
        const resultDiv = document.getElementById('draw-result');
        resultDiv.classList.add('empty');
        resultDiv.innerHTML = '<p>Cliquez sur le bouton pour effectuer un tirage</p>';

        updateRemainingCounts();
        saveData();
        showAlert('success', 'Les listes ont été réinitialisées. Tous les apprenants et questions sont à nouveau disponibles.', 'config');
    }
}

// ==================== IMPORT/EXPORT ====================
function importLists() {
    document.getElementById('import-file').click();
}

function handleImport(input) {
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const content = e.target.result;
            const lines = content.split('\n');

            let inStudents = false;
            let inQuestions = false;
            let students = [];
            let questions = [];

            lines.forEach(line => {
                line = line.trim();
                if (line === '=== APPRENANTS ===') {
                    inStudents = true;
                    inQuestions = false;
                } else if (line === '=== QUESTIONS ===') {
                    inStudents = false;
                    inQuestions = true;
                } else if (line && !line.startsWith('===')) {
                    if (inStudents) students.push(line);
                    if (inQuestions) questions.push(line);
                }
            });

            if (students.length > 0 || questions.length > 0) {
                document.getElementById('students-list').value = students.join('\n');
                document.getElementById('questions-list').value = questions.join('\n');
                updateCounts();
                saveData();
                showAlert('success', `Import réussi! ${students.length} apprenants et ${questions.length} questions importés.`, 'config');
            } else {
                showAlert('error', 'Le fichier ne contient pas de données valides.', 'config');
            }
        } catch (error) {
            showAlert('error', 'Erreur lors de l\'import du fichier.', 'config');
        }
    };
    reader.readAsText(file);
    input.value = '';
}

function exportLists() {
    const studentsText = document.getElementById('students-list').value.trim();
    const questionsText = document.getElementById('questions-list').value.trim();

    if (!studentsText && !questionsText) {
        showAlert('error', 'Aucune donnée à exporter.', 'config');
        return;
    }

    const content = `=== APPRENANTS ===
${studentsText}

=== QUESTIONS ===
${questionsText}
`;

    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `tirage-listes-${new Date().toISOString().split('T')[0]}.txt`;
    a.click();
    URL.revokeObjectURL(url);

    showAlert('success', 'Listes exportées avec succès!', 'config');
}

// ==================== EXPORT PDF ====================
async function exportPDF() {
    if (drawHistory.length === 0) {
        showAlert('error', 'Aucun historique à exporter.', 'draw');
        return;
    }

    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // Charger le logo
        const logoUrl = window.location.origin + '/images/logo-horizontal.svg';

        // En-tête avec logo
        try {
            const logoImg = await loadImage(logoUrl);
            doc.addImage(logoImg, 'PNG', 15, 10, 30, 30);
        } catch (e) {
            console.warn('Logo non chargé:', e);
        }

        // Titre
        doc.setFontSize(20);
        doc.setFont(undefined, 'bold');
        doc.text('Historique des présentations', 50, 25);

        // Date
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        const date = new Date().toLocaleDateString('fr-CA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        doc.text(`Généré le ${date}`, 50, 32);

        // Ligne de séparation
        doc.setDrawColor(52, 152, 219);
        doc.setLineWidth(0.5);
        doc.line(15, 45, 195, 45);

        // Tableau d'historique
        let y = 55;
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text('#', 15, y);
        doc.text('Apprenant', 30, y);
        doc.text('Question', 80, y);

        y += 7;
        doc.setDrawColor(200, 200, 200);
        doc.line(15, y, 195, y);

        y += 7;
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');

        drawHistory.forEach((item, index) => {
            if (y > 270) {
                doc.addPage();
                y = 20;
            }

            // Numéro
            doc.text((index + 1).toString(), 15, y);

            // Apprenant
            const student = doc.splitTextToSize(item.student, 45);
            doc.text(student, 30, y);

            // Question
            const question = doc.splitTextToSize(item.question, 110);
            doc.text(question, 80, y);

            const maxLines = Math.max(student.length, question.length);
            y += maxLines * 5 + 3;

            // Ligne séparatrice
            doc.setDrawColor(240, 240, 240);
            doc.line(15, y, 195, y);
            y += 4;
        });

        // Pied de page avec texte Loi 25
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);

            // Ligne de séparation
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(0.3);
            doc.line(15, 275, 195, 275);

            // Texte Loi 25
            doc.setFontSize(8);
            doc.setFont(undefined, 'bold');
            doc.text('Protection des données personnelles (Loi 25)', 15, 280);

            doc.setFont(undefined, 'normal');
            doc.setFontSize(7);
            const footerText = [
                'Aucune donnée n\'est transmise ou stockée sur nos serveurs',
                'Toutes les informations restent dans votre navigateur local',
                'Les données sont automatiquement effacées à la fermeture de l\'onglet',
                'Vous pouvez supprimer manuellement les données à tout moment'
            ];

            let footerY = 284;
            footerText.forEach(line => {
                doc.text(line, 15, footerY);
                footerY += 3;
            });

            // URL et mention
            doc.setFontSize(7);
            doc.setTextColor(100, 100, 100);
            doc.text('Ce document a été généré localement sur votre appareil.', 15, 295);
            doc.text('Source: https://laveille.ai', 15, 299);

            // Numéro de page
            doc.text(`Page ${i} / ${pageCount}`, 180, 299);
        }

        // Télécharger
        const filename = `historique-presentations-${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(filename);

        showAlert('success', 'PDF téléchargé avec succès!', 'draw');

    } catch (error) {
        console.error('Erreur lors de la génération du PDF:', error);
        showAlert('error', 'Erreur lors de la génération du PDF.', 'draw');
    }
}

// Fonction helper pour charger une image
function loadImage(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = url;
    });
}

// ==================== PLEIN ÉCRAN ====================
function toggleFullscreen() {
    const container = document.querySelector('.tool-container');

    if (!document.fullscreenElement) {
        if (container.requestFullscreen) {
            container.requestFullscreen();
        } else if (container.webkitRequestFullscreen) {
            container.webkitRequestFullscreen();
        } else if (container.msRequestFullscreen) {
            container.msRequestFullscreen();
        }
        document.body.classList.add('fullscreen-active');
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        document.body.classList.remove('fullscreen-active');
    }
}

// ==================== ALERTES ====================
function showAlert(type, message, tab) {
    const alertId = tab + '-alert';
    const alertDiv = document.getElementById(alertId);

    const iconMap = {
        'success': '\u2713',
        'error': '\u2717'
    };

    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <span class="alert-icon">${iconMap[type]}</span>
        <span>${message}</span>
    `;
    alertDiv.style.display = 'flex';

    // Auto-hide après 5 secondes
    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000);
}

// ==================== UTILITAIRES ====================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
