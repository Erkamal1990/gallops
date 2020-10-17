app = angular.module("gallopsHyundai", ['ngRoute', 'ngValidate','ngSanitize', 'ksSwiper','ui.swiper']);
var base_url = 'http://127.0.0.1/gallops/';
var apiKey = "YzMxYjMyMzY0Y2UxOWNhOGZjZDE1MGE0MTdlY2NlNTg=";

app.config(['$locationProvider', '$routeProvider', '$validatorProvider',
function($locationProvider, $routeProvider, $validatorProvider) {

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
	
	$routeProvider.when("/", {
		templateUrl : "templates/homepage.html?ver=0.0.1",
		controller : "homeController",
		title : "Kutchcarz | Car Rental Service In Bhuj Kutch"
	})
	.otherwise({
		redirectTo : "/"
	});

	$locationProvider.html5Mode(true);

}]);

app.run(function($rootScope, $location, $http, $window, $routeParams,$filter) {
	$rootScope.$on('$routeChangeStart', function(evt, current, previous,$filter, next) {
		$rootScope.base_url = base_url;
		$rootScope.screenWidth = screen.width;
		$rootScope.activePath = $location.path();
		$rootScope.pageContent = "";
	});
});
app.directive('numbersOnly', function () {
    return {
        require: 'ngModel',
        link: function (scope, element, attr, ngModelCtrl) {
            function fromUser(text) {
                if (text) {
                    var transformedInput = text.replace(/[^0-9]/g, '');

                    if (transformedInput !== text) {
                        ngModelCtrl.$setViewValue(transformedInput);
                        ngModelCtrl.$render();
                    }
                    return transformedInput;
                }
                return undefined;
            }            
            ngModelCtrl.$parsers.push(fromUser);
        }
    };
});
/*


app.directive('focusClass', function(){
    return {
      link:function(scope, elem, attrs){
         elem.find('input').on('focus', function(){
            elem.toggleClass(attrs.focusClass);
         }).on('blur', function(){
            elem.toggleClass(attrs.focusClass);
         });
      }
    }
});
app.filter("trustUrl", ['$sce',
function($sce) {
	return function(recordingUrl) {
		return $sce.trustAsResourceUrl(recordingUrl);
	};
}]);

app.filter('sanitizer', ['$sce',
function($sce) {
	return function(url) {
		return $sce.trustAsHtml(url);
	};
}]);
*/

app.controller("MainController", function($scope, $location, $rootScope, $timeout, $http, $routeParams, $window, $route) {

	$rootScope.siteLoading = true;
	$rootScope.loadSite = false;
	$(window).load(function() {
		$timeout(function() {
			$rootScope.siteLoading = false;
		}, 1000);
		$timeout(function() {
			$rootScope.loadSite = true;
		}, 1500);
	})

	$rootScope.isMobileMenuOpen = false;
	$rootScope.toggleMobileMenu = function(){
		if($rootScope.isMobileMenuOpen == false){
			$rootScope.isMobileMenuOpen = true;
		} else {
			$rootScope.isMobileMenuOpen = false;
		}
	}
	$rootScope.closeMenu = function(){
		$rootScope.isMobileMenuOpen = false;
	}

	$rootScope.mobileScrollTo = function(div){
		var topPosition = $("#"+div).position();

		$("html, body").animate({scrollTop: topPosition.top});
		/*$('html, body').animate({
	        scrollTop: $("#"+div).offset().top
	    }, 2000);*/
	}

	$scope.mainSlider = {
        loop : true,
        autoplay: {
		   delay: 6000,
		},
		speed: 2000,
		effect: 'fade',
        showNavButtons:true,
        slidesPerView : 1,
        spaceBetween :0,
        paginationClickable : true,
        lazy: true,
        pagination: {
		    el: '.swiper-pagination.mainSlider',
		    dynamicBullets: true,
		    type: 'bullets',
	  	},
        navigation: {
			nextEl: '.swiper-button-next.mainSliderArrow',
			prevEl: '.swiper-button-prev.mainSliderArrow',
		},
    };
});

app.controller("homeController", function($scope, $location, $rootScope, $timeout, $http, $routeParams, $window, $route) {
	$scope.isSubmitting = false;
	$scope.contactObj = {};
	$scope.contactObj.vehicle = "";
	$scope.contactObj.date = new Date();
	
	$scope.formValidate = {
		onkeyup : function(element) {
			this.element(element);
		},
		messages : {
			full_name : {
				required : "Enter Full Name.",
			},
			contact : {
				required : "Enter Contact Numbers.",
			}
		}
	};

	$scope.clickScroll = function(hash){
		$(".main").moveTo(hash);
	}
	
	$scope.submitContact = function(form){
		if(form.validate() && !$scope.isSubmitting){
			$scope.isSubmitting = true;
			$scope.contactObj.apiKey = "6LfSb1EUAAAAACSH6o1CWiFfsXUW5-Fn1dlI7zvE";
			$scope.contactObj.logo = base_url+'assets/images/logo.png';
			$http({
				method:'POST',
				url: base_url + 'mail/contact.php',
				data: $scope.contactObj,
			 	headers : {'Content-Type': 'application/x-www-form-urlencoded'}
			}).then(function successCallback(response){
				response =  response.data;
				$scope.isSubmitting = false;
				if(response.success == 1){
					$scope.contactObj = {};
					$scope.contactObj.vehicle = "";
					$scope.contactObj.date = new Date();
				}
				alert(response.message);
			})
		}
	}
		
});

