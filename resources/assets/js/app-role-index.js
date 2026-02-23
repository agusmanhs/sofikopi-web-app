/**
 * Role Index Script
 */
document.addEventListener('DOMContentLoaded', function () {
    // Handle Delete Record
    const deleteButtons = document.querySelectorAll('.delete-record');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const actionUrl = this.getAttribute('data-action');
            
            window.AlertHandler.confirm(
                'Are you sure?',
                'You won\'t be able to revert this!',
                'Yes, delete it!',
                () => {
                    // Perform Delete Request
                    window.axios.delete(actionUrl)
                        .then(response => {
                            window.AlertHandler.handle(response);
                            // Optional: Reload page or remove row
                            if (response.data.success) {
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            }
                        })
                        .catch(error => {
                            if (error.response) {
                                window.AlertHandler.handle(error.response);
                            } else {
                                window.AlertHandler.showError('An error occurred');
                            }
                        });
                }
            );
        });
    });
});
