function findObj(n, d)
{
 //v4.01
    var p, i, x;

    if (!d) {
        d = document;
    }

    if ((p = n.indexOf("?")) > 0 && parent.frames.length) {
        d = parent.frames[n.substring(p + 1)].document;
        n = n.substring(0,p);
    }

    if (!(x = d[n]) && d.all) {
        x = d.all[n];
    }

    for (i = 0; !x && i < d.forms.length; i++) {
        x = d.forms[i][n];
    }

    for (i = 0; !x && d.layers && i < d.layers.length; i++) {
        x = findObj(n,d.layers[i].document);
    }

    if (!x && d.getElementById) {
        x = d.getElementById(n);
    }

    return x;
}

function validateForm()
{
 //v4.0
    var i, p, q, nm, test, num, min, max, errors = '', args = validateForm.arguments,pos;

    for (i = 0; i < (args.length - 2); i += 3) {
        test = args[i + 2];
        let val = findObj(args[i]);
        if (val) {
          //nm=val.name;
            nm = args[i + 1];
            if ((val = val.value) != "") {
                if (test.indexOf('isDatePasse') != -1) {
                    let today = new Date();
                    num = val.split('-');
                    let date_choisi = new Date(0);
                    date_choisi.setFullYear(num[0],num[1] - 1,num[2]);
                    if (today < date_choisi) {
                        errors += '- ' + nm + ' ne doit pas être une date dans le futur.\n';
                    }
                } else if (test.indexOf('isEmail') != -1) {
                    p = val.indexOf('@');
                    if (p < 1 || p == (val.length - 1)) {
                        errors += '- ' + nm + ' doit contenir une adresse électronique valide.\n';
                    }
                } else if (test.indexOf('isDate') != -1) {
                    num = val.split('-');
                    if (num.length < 3 || num[0].length != 4 || (num[1].length != 1 && num[1].length != 2) || (num[2].length != 1 && num[2].length != 2) || isNaN(num[0]) || isNaN(num[1]) || isNaN(num[2]) ) {
                        errors += '- ' + nm + ' doit contenir une date.\n';
                    }
                } else if (test.indexOf('isInt') != -1) {
                    num = parseInt(val);
                    if (parseInt(val) != val) {
                        errors += '- ' + nm + ' doit contenir un entier.\n';
                    }
                } else if (test.indexOf('isFloat') != -1) {
                    num = parseFloat(val);
                    if (isNaN(val)) {
                        errors += '- ' + nm + ' doit contenir un réel.\n';
                    }
                }


                if (test.indexOf('inRange') != -1) {
                    p = test.indexOf(':');
                    min = test.substring(8,p);
                    max = test.substring(p + 1);
                    if (num < min || max < num) {
                        errors += '- ' + nm + ' doit contenir un nombre entre ' + min + ' et ' + max + '.\n';
                    }
                }

                if ((pos = test.indexOf('maxLength')) != -1) {
                    p = test.indexOf('!');
                    let maxlength = parseInt(test.substring(pos + 9,p));
                    if (val.length > maxlength) {
                        errors += '- ' + nm + ' est limité à ' + maxlength + ' caractères maxi.\n';
                    }
                }

                if ((pos = test.indexOf('RegExp')) != -1) {
                    p = test.indexOf('#');
                    let pattern = test.substring(pos + 6, p);
                    let regexp = new RegExp(pattern);
                    if (! regexp.test(val)) {
                        errors += '- ' + nm + ' ne correspond pas au motif de caractères autorisés.\n';
                    }
                }
            } else if (test.charAt(0) == 'R') {
                errors += '- ' + nm + ' est requis.\n';
            }
        }
    }


    if (errors) {
        alert('Les erreurs suivantes ont été trouvées:\n' + errors);
        return false;
    }
    return true;
  //document.returnValue = (errors == '');
}
