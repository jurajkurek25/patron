// Video page interactivity

// Like button functionality
const likeBtn = document.getElementById('like-btn');
if (likeBtn) {
    likeBtn.addEventListener('click', async () => {
        const videoId = likeBtn.getAttribute('data-video-id');

        try {
            const response = await fetch('/api/like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ video_id: videoId })
            });

            const data = await response.json();

            if (data.error) {
                alert('Chyba: ' + data.error);
                return;
            }

            // Update like button state
            if (data.liked) {
                likeBtn.classList.add('liked');
            } else {
                likeBtn.classList.remove('liked');
            }

            // Update likes count
            document.getElementById('likes-count').textContent = data.likes_count;

        } catch (error) {
            alert('Nastala chyba: ' + error.message);
        }
    });
}

// Comment form functionality
const commentForm = document.getElementById('comment-form');
if (commentForm) {
    commentForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const commentText = document.getElementById('comment-text').value.trim();
        if (!commentText) {
            alert('Komentár nemôže byť prázdny');
            return;
        }

        // Get video ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const videoId = urlParams.get('id');

        try {
            const response = await fetch('/api/comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    video_id: videoId,
                    comment_text: commentText
                })
            });

            const data = await response.json();

            if (data.error) {
                alert('Chyba: ' + data.error);
                return;
            }

            // Add comment to list
            const comment = data.comment;
            const commentsList = document.getElementById('comments-list');

            const commentElement = document.createElement('div');
            commentElement.className = 'comment';
            commentElement.innerHTML = `
                <div class="comment-header">
                    <strong>${escapeHtml(comment.username)}</strong>
                    <span class="comment-time">práve teraz</span>
                </div>
                <div class="comment-text">
                    ${escapeHtml(comment.comment_text).replace(/\n/g, '<br>')}
                </div>
            `;

            commentsList.insertBefore(commentElement, commentsList.firstChild);

            // Clear form
            document.getElementById('comment-text').value = '';

            // Update comments count in heading if exists
            const commentsHeading = document.querySelector('.comments-section h2');
            if (commentsHeading) {
                const currentCount = parseInt(commentsHeading.textContent.match(/\d+/)?.[0] || 0);
                commentsHeading.textContent = `Komentáre (${currentCount + 1})`;
            }

        } catch (error) {
            alert('Nastala chyba: ' + error.message);
        }
    });
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Auto-expand textarea
const commentTextarea = document.getElementById('comment-text');
if (commentTextarea) {
    commentTextarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}
