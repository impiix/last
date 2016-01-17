var express = require('express');
var router = express.Router();
var mongoose = require('mongoose');
var order = require('../models/Order.js');

/* GET users listing. */
router.get('/', function(req, res, next) {
  order.find(function(err, todos) {
    if (err) return next(err);
    res.json(todos);
  });
});

router.get('/:id', function(req, res, next) {
  order.findById(req.param.id, function(err, post) {
    if(err) return next(err);
    res.json(order);
  });
});

module.exports = router;
