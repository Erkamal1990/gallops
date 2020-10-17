var app = angular.module("gallopsHyundaiAdmin", ['ngSanitize','ngRoute','base64','ngValidate','checklist-model','ngStorage','angular-md5','ui.bootstrap','ngMaterial','ngTagsInput','ngFileUpload', 'bw.paging', 'socialbase.sweetAlert',  'daterangepicker','colorpicker.module','chart.js']);

app.$inject = ['SweetAlert'];

var frontUrl = 'http://localhost/gallops/';
var rootUrl = 'http://localhost/gallops/admin/';
var apiUrl  = 'http://localhost/gallops/api/';

var apiId  = 'YzMxYjMyMzY0Y2UxOWNhOGZjZDE1MGE0MTdlY2NlNTg=';

app.config(['$locationProvider','$routeProvider','$validatorProvider',function($locationProvider,$routeProvider,$validatorProvider){
	$locationProvider.html5Mode({
	  	enabled: true,
	  	requireBase: false
	});

    /** Adding validation method for password **/
    $validatorProvider.addMethod("pwcheck", function(value, element, param) {
        return (/[A-Z]/.test(value) && /\d/.test(value) && /[$@$!%*#?&]/.test(value));
    }, 'Password must contain 1 special character, 1 Capital letter and 1 Digit!');

    /** Adding validation method for letters only **/
    $validatorProvider.addMethod("lettersonly", function(value, element) {
        return this.optional(element) || /^[a-z]+$/i.test(value);
    }, "Special characters and numbers are not allowed!");

    /** Adding validation method for letters only **/
    $validatorProvider.addMethod("alphaonly", function(value, element) {
        return this.optional(element) || /^[a-zA-Z\s]+$/i.test(value);
    }, "Special characters and numbers are not allowed!");

    $locationProvider.hashPrefix('');
    
    $validatorProvider.addMethod('notEqualTo', function(value, element, param) {
        var target = $(param);
        if (this.settings.onfocusout && target.not(".validate-equalTo-blur").length) {
            target.addClass("validate-equalTo-blur").on("blur.validate-equalTo", function() {
                $(element).valid();
            });
        }
        return value !== target.val();
    }, 'Please enter other string, string should be diffrent.');

    $validatorProvider.addMethod('validate_name', function(value, element) {
        /*return this.optional(element) || /^http:\/\/mydomain.com/.test(value);*/
        return (/^[A-Za-z]?[A-Za-z ]*$/.test(value));
        // has a digit
    }, 'Please enter valid name.');

    $validatorProvider.addMethod('floating_val', function(value, element) {
        /*return this.optional(element) || /^http:\/\/mydomain.com/.test(value);*/
        return (/^\d{1,5}([\.](\d{1,4})?)?$/.test(value));
        // has a digit
    }, 'Please enter valid value.');

    $routeProvider.when("/",{
		templateUrl : "templates/dashboard.html",
		controller  : 'mainPageController',
		title : "Dashboard",
		activeSidebarTab: "dashboard"
	})
	$routeProvider.when("/login",{
		templateUrl : "templates/login.html",
		controller : "LoginController",
		title : "Login",
		activeSidebarTab: ""
	})
    // Modal
    $routeProvider.when("/model/list",{
        templateUrl : "templates/model/list.html",
        controller : "modelListController",
        title : "Manage Model",
        activeSidebarTab: ""
    })

    $routeProvider.when("/model/add",{
        templateUrl : "templates/model/add.html",
        controller : "modelAddController",
        title : "Add Model",
        activeSidebarTab: ""
    })

    $routeProvider.when("/model/update/:category_id",{
        templateUrl : "templates/model/add.html",
        controller : "modelAddController",
        title : "Update Model",
        activeSidebarTab: ""
    })
    //Specification
    $routeProvider.when("/specification/list",{
        templateUrl : "templates/specification/list.html",
        controller : "specificationListController",
        title : "Manage Specification",
        activeSidebarTab: ""
    })

    $routeProvider.when("/specification/add",{
        templateUrl : "templates/specification/add.html",
        controller : "specificationAddController",
        title : "Add Specification",
        activeSidebarTab: ""
    })

    $routeProvider.when("/specification/update/:category_id",{
        templateUrl : "templates/specification/add.html",
        controller : "specificationAddController",
        title : "Update Specification",
        activeSidebarTab: ""
    })
    // variants
        $routeProvider.when("/variants/list",{
        templateUrl : "templates/variants/list.html",
        controller : "variantsListController",
        title : "Manage Variants",
        activeSidebarTab: ""
    })

    $routeProvider.when("/variants/add",{
        templateUrl : "templates/variants/add.html",
        controller : "variantsAddController",
        title : "Add Variants",
        activeSidebarTab: ""
    })

    $routeProvider.when("/variants/update/:category_id",{
        templateUrl : "templates/variants/add.html",
        controller : "variantsAddController",
        title : "Update Variants",
        activeSidebarTab: ""
    })
    // color variants
        $routeProvider.when("/colors/list",{
        templateUrl : "templates/colors/list.html",
        controller : "colorsListController",
        title : "Manage Colors",
        activeSidebarTab: ""
    })

    $routeProvider.when("/colors/add",{
        templateUrl : "templates/colors/add.html",
        controller : "colorsAddController",
        title : "Add Colors",
        activeSidebarTab: ""
    })

    $routeProvider.when("/colors/update/:category_id",{
        templateUrl : "templates/colors/add.html",
        controller : "colorsAddController",
        title : "Update Colors",
        activeSidebarTab: ""
    })
    // car Gallery
    $routeProvider.when("/gallery/list",{
        templateUrl : "templates/gallery/list.html",
        controller : "galleryListController",
        title : "Manage Gallery",
        activeSidebarTab: ""
    })

    $routeProvider.when("/gallery/add",{
        templateUrl : "templates/gallery/add.html",
        controller : "galleryAddController",
        title : "Add Gallery",
        activeSidebarTab: ""
    })

    $routeProvider.when("/gallery/update/:category_id",{
        templateUrl : "templates/gallery/add.html",
        controller : "galleryAddController",
        title : "Update Gallery",
        activeSidebarTab: ""
    })
	.otherwise({ redirectTo: "/" });
}]);

app.run(['$rootScope', '$route', function($rootScope, $route) {
    $rootScope.$on('$routeChangeSuccess', function() {
        document.title = $route.current.title;
    });
}]);
app.run(function ($rootScope, $location, $localStorage, $http){
	$rootScope.rootUrl = rootUrl;
	$rootScope.frontUrl = frontUrl;
	$rootScope.$on('$routeChangeSuccess', function(evt, current, previous){
		var path = $location.path();
		$rootScope.activeSidebarTab = (current.$$route.activeSidebarTab) ? current.$$route.activeSidebarTab : "";
		$rootScope.page_title = (current.$$route.title) ? current.$$route.title : "Gallops";
		$rootScope.activePath = $location.path();
	});
});
app.directive('hires', function() {
  return {
    restrict: 'A',
    scope: { hires: '@' },
    link: function(scope, element, attrs) {
        element.one('load', function() {
            element.attr('src', scope.hires);
        });
    }
  };
});
app.directive('decimalNumber', function() {
    return {
        require: 'ngModel',
        restrict: 'A',
        link: function(scope, element, attr, ctrl) {
            function inputValue(val) {
                if (val) {
                    val = val.toString();
                    var digits = val.replace(/[^0-9.]/g, '');
                    if (digits.split('.').length > 2) {
                        digits = digits.substring(0, digits.length - 1);
                    }
                    if (digits.split('.')[0] == "") {
                        digits = "0" + '.' + digits.split('.')[1];
                    }
                    if (digits.split('.')[1] && digits.split('.')[1].length > 8) {
                         digits = digits.split('.')[0] + '.' + digits.split('.')[1].substring(0, 8);
                    }
                    if (digits !== val) {
                        ctrl.$setViewValue(digits);
                        ctrl.$render();
                    }
                    return parseFloat(digits);
                }
                return "";
            }
            ctrl.$parsers.push(inputValue);
        }
    };
});

app.directive('ngFile', ['$parse', function ($parse) {
	return {
		restrict: 'A',
		link: function(scope, element, attrs) {
			element.bind('change', function(){
				$parse(attrs.ngFile).assign(scope,element[0].files)
				scope.$apply();
			});
		}
	};
}]);

app.filter('groupBy', function() {
    return _.memoize(function(items, field) {
		return _.groupBy(items, field);
	});
});

app.filter("trustUrl", ['$sce', function ($sce) {
    return function (recordingUrl) {
        return $sce.trustAsResourceUrl(recordingUrl);
    };
}]);

app.directive('modal', ['$document',
function($document) {
    return {
        template : '<div class="modal fade">' + '<div class="modal-dialog">' + '<div class="modal-content">' + '<div class="modal-header">' + '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' + '<h4 class="modal-title">{{ title }}</h4>' + '</div>' + '<div class="modal-body" ng-transclude></div>' + '</div>' + '</div>' + '</div>',
        restrict : 'E',
        transclude : true,
        replace : true,
        scope : true,
        link : function postLink(scope, element, attrs) {
            scope.title = attrs.title;
            scope.$watch(attrs.visible, function(value) {
                if (value == true) {
                    $(element).modal('show');
                    $('body').css({
                        "overflow" : "hidden"
                    });
                    $('.page-container').css({
                        "z-index" : "999999"
                    });
                } else {
                    $(element).modal('hide');
                    $('body').css({
                        "overflow" : "visible"
                    });
                    $('.page-container').css({
                        "z-index" : "999"
                    });
                }
            });
            $(element).on('shown.bs.modal', function() {
                scope.$apply(function() {
                    scope.$parent[attrs.visible] = true;
                    $('input:text:visible:first', this).focus();
                });
            });
            $(element).on('hidden.bs.modal', function() {
                scope.$apply(function() {
                    scope.$parent[attrs.visible] = false;
                });
            });
            function escHandler(event) {
                if (event.keyCode === 27) {
                    scope.$apply(function() {
                        scope.$parent[attrs.visible] = false;
                    });
                }
            }
            $document.on('keydown', escHandler);
        }
    };

}]);

app.directive('onlyNumbers', function () {
    return  {
        restrict: 'A',
        link: function (scope, elm, attrs, ctrl) {
            elm.on('keydown', function (event) {
                if(event.shiftKey){event.preventDefault(); return false;}
                if ([8, 13, 27, 37, 38, 39, 40].indexOf(event.which) > -1) {
                    return true;
                } else if (event.which >= 48 && event.which <= 57) {
                    return true;
                } else if (event.which >= 96 && event.which <= 105) {
                    return true;
                }  else {
                    event.preventDefault();
                    return false;
                }
            });
        }
    }
});

app.controller("MainController", function ($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams,$window, $route, $mdToast, $timeout, $base64,$interval,$timeout) {
	$scope.rootUrl = rootUrl;	
	$rootScope.$storage = $localStorage.$default({ 
		gallops_admin_id: null,
        ilsh_admin_cart_session : null
   	});	

    
   	$rootScope.toBase64 = function (string) {
        return $base64.encode(unescape(encodeURIComponent(string)));
    }
    $rootScope.fromBase64 = function (string) {
        return decodeURIComponent(escape($base64.decode(string)));
    }
	$scope.logout = function(){
        $rootScope.$storage.gallops_admin_id = null;
        $location.path('/login/');
    }
    
});

app.controller("mainPageController", function ($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams,$window, $route, $mdToast, $timeout) {
    if($rootScope.$storage.gallops_admin_id == null){
        $location.path('/login/');
    }
    $scope.tab_name = "";
    $rootScope.openSideMenu = function(tab){
        if(tab && $scope.tab_name != tab){
            $scope.tab_name = tab;    
        } else if($scope.tab_name == tab){
            $scope.tab_name = "";
        } else {
            $scope.tab_name = "";
        }
    }
   
});


app.controller("LoginController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){
	if($rootScope.$storage.gallops_admin_id){
		$location.path('/');
	}
	$scope.validateLoginForm = {
		onkeyup : function(element) {
			this.element(element);
		},
		rules:{
			email:{
				required:true,
				email:true
			},
			password:{
				required:true
			}
		},
		messages:{
			email:{
				required:"Email address can not be blank.",
				email:"Enter a valid email address."
			},
			password:{
				required:"Password can not be blank."
			}
		}
	}
	$scope.isSubmitting = false;
	$scope.LoginText = "Login";
	$scope.loginResponse = {};
	$scope.submitLogin = function(form){
        $scope.admindata.apiId = apiId;
		if(form.validate()){
			$scope.isSubmitting = true;
			$scope.loginResponse = {};
			$scope.LoginText = "Logging in...";
			$http({
				method : 'POST',
				url : apiUrl + 'services/login/signin',
				data : $scope.admindata
			}).then(function successCallback(response) {
				response = response.data;
				if(response.success == 1){
					$rootScope.$storage.gallops_admin_id = response.admin.admin_id;
					$location.path("/");
				}
				$scope.isSubmitting = false;
				$scope.LoginText = "Login";
				$scope.loginResponse.success = response.success;
				$scope.loginResponse.message = response.message;
			}, function errorCallback(response) {
			});	
		}
	}
});


// app.controller("categoryAddController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

//     if(!$rootScope.$storage.gallops_admin_id){
//         $location.path('/');
//     }

//     $scope.itemObj = {};
//     $scope.itemObj.status = "1";


//     $scope.loadingItem = false;
//     if($rootScope.activePath.indexOf("update") !== -1){
//         if($routeParams.category_id){
//             $scope.loadingItem = true;
//             $http({
//                 method:'POST',
//                 url:apiUrl + 'services/categories/details',
//                 data : {
//                     category_id : $routeParams.category_id ? $rootScope.fromBase64($routeParams.category_id) : "",
//                     apiId : apiId,
//                     from_app : true
//                 }
//             }).then(function successCallback(response){
//                 response = response.data;
//                 if(response.success == 1){
//                     $scope.itemObj = response.category;
//                 }
//                 $scope.loadingItem = false;
//             }, function errorCallback(response) {
//                 $scope.loadingItem = false;
//             });  
//         } else {
//             $mdToast.show({
//                 template : '<md-toast class="md-toast error">Invalid restaurant id</md-toast>',
//                 hideDelay : 2000,
//                 position : 'bottom right'
//             });
//         }
//     }

//     $scope.activeFocused = [0]; 
//     $scope.checkFocused = function(index){
//         if($scope.activeFocused.indexOf(index) == -1){
//             $scope.activeFocused.push(index);
//         }
//     }
//     $scope.activeFocusedVariation = [0]; 
//     $scope.checkFocusedVariation = function(index){
//         if($scope.activeFocusedVariation.indexOf(index) == -1){
//             $scope.activeFocusedVariation.push(index);
//         }
//     }


//     $scope.saving = false;
//     $scope.saveItem = function(form){
//         if(form.validate() && !$scope.saving){
            
//             $scope.saving = true;
//             var data = new FormData();
//             $scope.itemObj.from_app = 'true';
//             $scope.itemObj.apiId = apiId;

//             if($routeParams.category_id){
//                 $scope.itemObj.category_id = $rootScope.fromBase64($routeParams.category_id);
//             }

//             angular.forEach($scope.itemObj, function (val, key) {
//                 data.append(key, val);
//             })
            
//             $http({
//                 method:'POST',
//                 url:apiUrl + 'services/categories/save',
//                 headers: {'Content-Type': undefined},
//                 data: data
//             }).then(function successCallback(response){
//                 response = response.data;
//                 if(response.success == 1){
//                     $mdToast.show({
//                         template : '<md-toast class="md-toast error">'+response.message+'</md-toast>',
//                         hideDelay : 2000,
//                         position : 'bottom right'
//                     }); 
//                     $location.path("/categories/list");
//                 }else{
//                     $mdToast.show({
//                         template : '<md-toast class="md-toast error">'+response.message+'</md-toast>',
//                         hideDelay : 2000,
//                         position : 'bottom right'
//                     });                 
//                 }
//                 $scope.saving = false;
//             }, function errorCallback(response){
//                 $scope.saving = false;
//             });
//         }
//     }

// });

// app.controller("categoryListController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

//     if(!$rootScope.$storage.gallops_admin_id){
//         $location.path("/");
//     }


//     $scope.productList = [];

//     $scope.search_obj = {};
//     $scope.search_obj.status = "";
//     $scope.search_obj.menu_id = "";

//     $scope.filter_text = "All";
//     $scope.filterStatus = function(status){
//         if(status){
//             if(status == 1){
//                 $scope.filter_text = "Active";    
//             }
//             if(status == 0){
//                 $scope.filter_text = "Inactive";    
//             }
//         } else {
//             $scope.filter_text = "All";
//         }
//         $scope.search_obj.status = status;
//         $scope.loadCategory();
//     }

//     $scope.categoryList = [];
//     $scope.loadCategory = function(){
//         $http({
//             method : 'POST',
//             url : apiUrl + 'services/categories/list',
//             data : {
//                 apiId : apiId,
//                 search : $scope.search_obj.search,
//                 status : $scope.search_obj.status,
//             }
//         }).then(function successCallback(response) {
//             response = response.data;
//             $scope.categoryList = [];
//             if(response.success == 1){
//                 $scope.categoryList = response.categories;
//             }
            
//         }, function errorCallback(response) {
//       });     
//    }
//    $scope.loadCategory();


//     $scope.changeStatus = function(category_id, index){
//         $http({
//             method : 'POST',
//             url : apiUrl + 'services/categories/statusupdate',
//             data : {
//                 category_id : category_id,
//                 apiId : apiId,
//                 from_app : true
//             }
//         }).then(function successCallback(response) {
//             response = response.data;
//             if(response.success == 1){
//                 if($scope.categoryList[index].status == 1){
//                     $scope.categoryList[index].status = 0;
//                 } else {   
//                     $scope.categoryList[index].status = 1;
//                 }
//                 $scope.loadCategory();
//             }
//         }, function errorCallback(response) {
//         });
//     }


//     $scope.remove = function(category_id, index){
//         swal({
//             text: "Are you sure, You want to perform this operation?",
//             type: 'warning',
//             showCancelButton: true,
//             cancelButtonColor: '#c2935b',
//             confirmButtonColor: '#a4cdc1',
//             confirmButtonText: 'Yes'

//         }).then(function () {
//             $http({
//                 method : 'POST',
//                 url : apiUrl + 'services/categories/remove',
//                 data : {
//                     category_id : category_id,
//                     apiId : apiId,
//                     from_app :true
//                 }
//             }).then(function successCallback(response) {
//                 response = response.data;
//                 if(response.success == 1){
//                     $scope.categoryList.splice(index, 1);
//                 }
//                 $mdToast.show({
//                     template : '<md-toast class="md-toast error">'+response.message+'</md-toast>',
//                     hideDelay : 2000,
//                     position : 'bottom right'
//                 });
//             }, function errorCallback(response) {
//             });
//         });
//     }
// });
// Car Modal
app.controller("modelAddController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
app.controller("modelListController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
// Car Modal
// Car Specification
app.controller("specificationAddController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
app.controller("specificationListController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
// Car Specification
// Car variants
app.controller("variantsAddController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
app.controller("variantsListController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
// Car variants
// Car color variants 
app.controller("colorsAddController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
app.controller("colorsListController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
// Car color variants
// Car gallery 
app.controller("galleryAddController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
app.controller("galleryListController", function($scope, $location, $rootScope, $timeout, $http, $localStorage, $routeParams, md5, $window, $route, $base64, $mdToast){

    if(!$rootScope.$storage.gallops_admin_id){
        $location.path('/');
    }
});
// Car gallery

