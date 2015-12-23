var lastApp = angular.module("lastApp", []);

lastApp.controller("OrderCtrl", function ($scope, $http, $interval) {



    $interval(function() {
       $scope.load();
    }, 5000);

    $scope.load = function () {
        $http.get('orders.json').success(function(data) {
            $scope.orders = data;
        });
    };

    $scope.load();

    $scope.orderProp = "created_at";
});
