(function ($, app) {
    'use strict';
    var eclickFlag = false;
    var rulesForm = {
        payId: null,
        payCode: "",
        payEdesc: "",
        payLdesc: "",
        payTypeFlag: 'A',
        priorityIndex: 0,
        remarks: "",
        setRulesFromRemote: function (rules) {
            this.payId = rules.PAY_ID;
            this.payCode = rules.PAY_CODE;
            this.payEdesc = rules.PAY_EDESC;
            this.payLdesc = rules.PAY_LDESC;
            this.payTypeFlag = rules.PAY_TYPE_FLAG;
            this.priorityIndex = rules.PRIORITY_INDEX;
            this.remarks = rules.REMARKS;
        },
        setRulesFromView: function (payCode, payEdesc, payLdesc, payTypeFlag, priorityIndex, remarks) {
            this.payCode = payCode;
            this.payEdesc = payEdesc;
            this.payLdesc = payLdesc;
            this.payTypeFlag = payTypeFlag;
            this.priorityIndex = priorityIndex;
            this.remarks = remarks;

        }
    };
    var ruleDetail = {
        srNo: null,
        mnenonicName: "",
        setRuleDetailFromRemote: function (ruleDetail) {
            this.srNo = ruleDetail.SR_NO;
            this.mnenonicName = ruleDetail.MNENONIC_NAME;
        },
        updateModel: function (mne) {
            this.mnenonicName = mne;
        },
        updateView: function () {
            editor.setValue(this.mnenonicName);
        },
        pullRuleDetailByPayId: function (payId) {
            var obj = this;
            app.pullDataById(document.url, {
                action: 'pullRuleDetailByPayId',
                data: {payId: payId}
            }).then(function (success) {
                console.log("success", success);
                if (!((typeof success.data === 'undefined') || (success.data == null))) {
                    obj.setRuleDetailFromRemote(success.data)
                    obj.updateView();
                }
            }, function (failure) {
                console.log("failure", failure);
            });
        }
    };
    var positionAssigned = {
        remotePositions: [],
        positions: [],
        setPositionsFromRemote: function (positions) {
            for (var i in positions) {
                this.remotePositions.push(positions[i]["POSITION_ID"]);
                this.positions.push(positions[i]["POSITION_ID"]);
            }
        },
        updatePositions: function (id, status) {
            var filtered = this.positions.filter(function (value) {
                return value == id;
            });
            if (status && (filtered.length == 0)) {
                this.positions.push(id);
            } else if (!status && (filtered.length > 0)) {
                this.positions.splice(this.positions.indexOf(id), 1);
            }
        },
        updateView: function (id) {
            var positionListUl = $("#" + id);
            positionListUl.empty();
            var positionIds = Object.keys(document.positions);
            for (var pI in positionIds) {
                var checked = false;
                for (var sI in this.positions) {
                    if (positionIds[pI] == this.positions[sI]) {
                        checked = true;
                    }
                }
                var checkedHtml = checked ? 'checked' : '';
                positionListUl.append("<div class='md-checkbox'>" +
                    "<input type='checkbox' class='md-check' id='positionCB" + positionIds[pI] + "'  p-id='" + positionIds[pI] + "' " + checkedHtml + ">" +
                    "<label for='positionCB" + positionIds[pI] + "'>" +
                    "<span></span>" +
                    "<span class='check'></span>" +
                    "<span class='box'></span>" +
                    document.positions[positionIds[pI]] +
                    "</label>" +
                    "</div>")
                $("#positionCB" + positionIds[pI]).on('change', function () {
                    console.log($(this).prop('checked'));
                    positionAssigned.updatePositions($(this).attr('p-id'), $(this).prop('checked'));
                });
            }

        },
        pullPositionAssignedByPayId: function (payId) {
            var obj = this;
            app.pullDataById(document.url, {
                action: 'pullPositionsAssignedByPayId',
                data: {payId: payId}
            }).then(function (success) {
                console.log("success", success);
                if (typeof success.data !== 'undefined') {
                    obj.setPositionsFromRemote(success.data)
                    obj.updateView("positionList");
                }
            }, function (failure) {
                console.log("failure", failure);
            });
        },
        pushPositionAssigned: function () {
            var notChangedPositions = [];
            var needsToBeDeleted = [];
            var needsToBeAdded = [];
            for (var i in this.remotePositions) {
                var isNotChanged = false;
                for (var j in this.positions) {
                    if (this.remotePositions[i] == this.positions[j]) {
                        isNotChanged = true;
                    }
                }
                if (isNotChanged) {
                    notChangedPositions.push(this.remotePositions[i]);
                } else {
                    needsToBeDeleted.push(this.remotePositions[i]);
                }
            }


            for (var i in this.positions) {
                var common = false;
                for (var j in notChangedPositions) {
                    if (this.positions[i] == notChangedPositions[j]) {
                        common = true;
                    }
                }
                if (!common) {
                    needsToBeAdded.push(this.positions[i]);
                }
            }
            console.log("not changed", notChangedPositions);
            console.log("delete", needsToBeDeleted);
            console.log("add", needsToBeAdded);

            var promises = [];
            if (needsToBeAdded.length > 0) {
                promises.push(app.pullDataById(document.url, {
                    action: 'addPositionAssigned',
                    data: {
                        positions: needsToBeAdded,
                        payId: rulesForm.payId
                    }
                }));
            }

            if (needsToBeDeleted.length > 0) {
                promises.push(app.pullDataById(document.url, {
                    action: 'deletePositionAssigned',
                    data: {
                        positions: needsToBeDeleted,
                        payId: rulesForm.payId
                    }
                }));
            }
            Promise.all(promises)
                .then(function (success) {
                    console.log('PositionAssigned', success);
                    eclickFlag = true;
                    $('.button-next').click();
                    eclickFlag = false;
                }, function (failure) {
                    console.log('PositionAssigned', failure);
                });
        }
    }

    var setFormData = function () {
        $('#payCode').val(rulesForm.payCode);
        $('#payEdesc').val(rulesForm.payEdesc);
        $('#payLdesc').val(rulesForm.payLdesc);
        var $radios = $('input:radio[name=payTypeFlag]');
        // if ($radios.is(':checked') === false) {
        $radios.filter('[value=' + rulesForm.payTypeFlag + ']').prop('checked', true);
        // }
        // $("input[name=payTypeFlag]:checked").val();
        $('#priorityIndex').val(rulesForm.priorityIndex);
        $('#remarks').val(rulesForm.remarks);
    };

    var pushRuleDetail = function () {
        ruleDetail.payId = rulesForm.payId;
        ruleDetail.updateModel(editor.getValue());
        app.pullDataById(document.url, {
            action: 'pushRuleDetail',
            data: JSON.parse(JSON.stringify(ruleDetail))
        }).then(function (success) {
            console.log("success", success);
            eclickFlag = true;
            $('.button-next').click();
            positionAssigned.pullPositionAssignedByPayId(rulesForm.payId);
            eclickFlag = false;
        }, function (failure) {
            console.log("failure", failure);
        });
    };
    var editor;

    var initializeCodeMirror = function () {
        editor = CodeMirror.fromTextArea(document.getElementById('rule'), {
            lineNumbers: true
        });
    };

    var FormWizard = function () {
        return {
            init: function () {
                function e(e) {
                    return e.id ? "<img class='flag' src='../../assets/global/img/flags/" + e.id.toLowerCase() + ".png'/>&nbsp;&nbsp;" + e.text : e.text
                }

                if (jQuery().bootstrapWizard) {
                    var r = $("#submit_form"), t = $(".alert-danger", r), i = $(".alert-success", r);
                    var a = function () {
                        $("#tab4 .form-control-static", r).each(function () {
                            var e = $('[name="' + $(this).attr("data-display") + '"]', r);
                            if (e.is(":radio") && (e = $('[name="' + $(this).attr("data-display") + '"]:checked', r)), e.is(":text") || e.is("textarea"))$(this).html(e.val()); else if (e.is("select"))$(this).html(e.find("option:selected").text()); else if (e.is(":radio") && e.is(":checked"))$(this).html(e.attr("data-title")); else if ("payment[]" == $(this).attr("data-display")) {
                                var t = [];
                                $('[name="payment[]"]:checked', r).each(function () {
                                    t.push($(this).attr("data-title"))
                                }), $(this).html(t.join("<br>"))
                            }
                        })
                    }, o = function (e, r, t) {
                        var i = r.find("li").length, o = t + 1;
                        $(".step-title", $("#form_wizard_1")).text("Step " + (t + 1) + " of " + i), jQuery("li", $("#form_wizard_1")).removeClass("done");
                        for (var n = r.find("li"), s = 0; t > s; s++)jQuery(n[s]).addClass("done");
                        1 == o ? $("#form_wizard_1").find(".button-previous").hide() : $("#form_wizard_1").find(".button-previous").show(), o >= i ? ($("#form_wizard_1").find(".button-next").hide(), $("#form_wizard_1").find(".button-submit").show(), a()) : ($("#form_wizard_1").find(".button-next").show(), $("#form_wizard_1").find(".button-submit").hide()), App.scrollTo($(".page-title"))
                    };
                    $("#form_wizard_1").bootstrapWizard({
                        nextSelector: ".button-next",
                        previousSelector: ".button-previous",
                        onTabClick: function (e, r, t, i) {
                            return !1
                        },
                        onNext: function (e, a, n) {
                            if (eclickFlag) {
                                return i.hide(), t.hide(), void o(e, a, n);
                            }
                            switch (n) {
                                case 1:
                                    $('#Rules').submit();
                                    break;
                                case 2:
                                    pushRuleDetail();
                                    break;
                                case 3:
                                    positionAssigned.pushPositionAssigned();
                                    break;
                            }
                            return false;

                        },
                        onPrevious: function (e, r, a) {
                            i.hide(), t.hide(), o(e, r, a)
                        },
                        onTabShow: function (e, r, t) {
                            var i = r.find("li").length, a = t + 1, o = a / i * 100;
                            $("#form_wizard_1").find(".progress-bar").css({width: o + "%"})
                        }
                    }), $("#form_wizard_1").find(".button-previous").hide(), $("#form_wizard_1 .button-submit").click(function () {
                        alert("Finished! Hope you like it :)")
                    }).hide(), $("#country_list", r).change(function () {
                        r.validate().element($(this))
                    })
                }
            }
        }
    }();

    var replaceAll = function (rule, val, newVal) {
        if (rule.indexOf(val) >= 0) {
            rule = rule.replace(val, newVal);
            return replaceAll(rule, val, newVal);
        } else {
            return rule;
        }
    }
    var addValue = function (item) {
        console.log($(item).val());

    };


    window.MAX = function (val) {
        return 100 * val;
    }


    $(document).ready(function () {
        FormWizard.init();
        $('#Rules').validate({
            doNotHideMessage: !0,
            errorElement: "span",
            errorClass: "help-block help-block-error",
            focusInvalid: !1,
            rules: {
                payCode: {maxlength: 4, required: !0},
                payEdesc: {maxlength: 100, required: !0},
                payLdesc: {maxlength: 100, required: !0},
                priorityIndex: {required: !0},
                remarks: {maxlength: 255, required: !0}
            },
            messages: {},
            submitHandler: function (form) {
                rulesForm.setRulesFromView(
                    $('#payCode').val(),
                    $('#payEdesc').val(),
                    $('#payLdesc').val(),
                    $("input[name=payTypeFlag]:checked").val(),
                    $('#priorityIndex').val(),
                    $('#remarks').val()
                );

                console.log(rulesForm);


                app.pullDataById(document.url, {
                    action: 'pushRule',
                    data: JSON.parse(JSON.stringify(rulesForm))
                }).then(function (success) {
                    if (typeof success.data !== 'undefined') {
                        rulesForm.payId = success.data.payId;
                    }

                    eclickFlag = true;
                    $('.button-next').click();
                    eclickFlag = false;
                    initializeCodeMirror();
                    ruleDetail.pullRuleDetailByPayId(rulesForm.payId);

                }, function (failure) {
                    console.log("failure", failure);

                });
            }
        });

        if ((typeof  document.ruleId !== 'undefined') && (document.ruleId != 0)) {
            app.pullDataById(document.url, {
                action: 'pullRule',
                data: {ruleId: document.ruleId}
            }).then(function (success) {
                console.log("success", success);
                rulesForm.setRulesFromRemote(success.data.rule);
                setFormData();
            }, function (failure) {
                console.log("failure", failure);
            });
        }

        var monthlyValues = document.monthlyValues;
        var flatValues = document.flatValues;
        var variables=document.variables;
        var systemRules=document.systemRules;

        for (var i in monthlyValues) {
            monthlyValues[i] = replaceAll(monthlyValues[i], " ", "_");
            monthlyValues[i]=monthlyValues[i].toUpperCase();
            $('#monthlyValueList').append("<button class='list-group-item btn' id='vars' >" + monthlyValues[i] + "</button>");
        }

        for (var i in flatValues) {
            flatValues[i] = replaceAll(flatValues[i], " ", "_");
            flatValues[i]=flatValues[i].toUpperCase();
            $('#flatValueList').append("<button class='list-group-item btn' id='vars' > " + flatValues[i] + "</button>");
        }

        for (var i in variables) {
            $('#variables').append("<button class='list-group-item btn' id='vars' >" + variables[i] + "</button>");
        }

        for (var i in systemRules) {
            $('#systemRules').append("<button class='list-group-item btn' id='vars' >" + systemRules[i] + "</button>");
        }




        $('#check').on("click", function (event) {
            var rule = editor.getValue();
            for (var i in monthlyValues) {
                rule = replaceAll(rule, monthlyValues[i], 1);
            }

            for (var i in flatValues) {
                rule = replaceAll(rule, flatValues[i], 1);
            }
            try {
                console.log("before eval", rule);
                console.log(eval(rule));
            } catch (e) {
                if (e instanceof SyntaxError) {
                    alert(e.message);
                }
            }


        });


        var vars = document.querySelectorAll('#vars');
        for (var i = 0; i < vars.length; i++) {
            $(vars[i]).on('click', function () {
                var $this = $(this);
                editor.setValue(editor.getValue() + $this.text());
            });
        }


    });


})(window.jQuery, window.app);