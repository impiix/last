/**
 * Created by px on 1/11/16.
 */
var mongoose = require('mongoose');

var orderSchema = new mongoose.Schema({
    username: String,
    status: String,
    tracksCountAdded: Number,
    createdAt: {type: Date, default: Date.now}
});

module.exports = mongoose.model('Order', orderSchema);
