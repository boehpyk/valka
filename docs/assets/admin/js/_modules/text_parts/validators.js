$().ready(function() {    
    $('.add-form').bootstrapValidator({
        message: 'Введенное значение неверно!',
        feedbackIcons: {
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            title: {
                message: 'Неверное значение в поле Заголовок',
                validators: {
                    notEmpty: {
                        message: 'Поле "Заголовок" не может быть пустым'
                    },
                }
            }
        }
    });    
});        