<template>
    <div class="p-4">
        <h1 class="text-2xl font-bold mb-4">Customer Management</h1>

        <!-- Customer List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Customers</h3>
                <button @click="showAddCustomerModal = true" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Add Customer
                </button>
            </div>
            <div class="border-t border-gray-200">
                <ul v-if="customers.length">
                    <li v-for="customer in customers" :key="customer.id" class="px-4 py-4 sm:px-6 border-b border-gray-200 flex justify-between items-center hover:bg-gray-50">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ customer.name }}</p>
                            <p class="text-sm text-gray-500">{{ customer.email }}</p>
                            <p v-if="customer.phone_number" class="text-sm text-gray-500">{{ customer.phone_number }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <button @click="editCustomer(customer)" class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</button>
                            <button @click="deleteCustomer(customer.id)" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                        </div>
                    </li>
                </ul>
                <p v-else class="px-4 py-4 text-center text-gray-500">No customers found.</p>
            </div>
        </div>

        <!-- Add/Edit Customer Modal -->
        <div v-if="showAddCustomerModal || showEditCustomerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">{{ editingCustomer ? 'Edit Customer' : 'Add New Customer' }}</h3>
                <form @submit.prevent="editingCustomer ? updateCustomer() : addCustomer()">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" id="name" v-model="customerForm.name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md p-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" v-model="customerForm.email" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md p-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="text" id="phone_number" v-model="customerForm.phone_number" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md p-2">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="closeModal" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ editingCustomer ? 'Update' : 'Add' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
    data() {
        return {
            customers: [],
            customerForm: {
                name: '',
                email: '',
                phone_number: '',
            },
            editingCustomer: null,
            showAddCustomerModal: false,
            showEditCustomerModal: false,
        };
    },
    mounted() {
        this.fetchCustomers();
    },
    methods: {
        fetchCustomers() {
            axios.get('/api/customers')
                .then(response => {
                    this.customers = response.data;
                })
                .catch(error => {
                    console.error('Error fetching customers:', error);
                });
        },
        addCustomer() {
            axios.post('/api/customers', this.customerForm)
                .then(response => {
                    this.customers.push(response.data);
                    this.closeModal();
                    this.resetForm();
                })
                .catch(error => {
                    console.error('Error adding customer:', error);
                });
        },
        editCustomer(customer) {
            this.editingCustomer = { ...customer };
            this.customerForm = { ...customer };
            this.showEditCustomerModal = true;
        },
        updateCustomer() {
            axios.put(`/api/customers/${this.editingCustomer.id}`, this.customerForm)
                .then(response => {
                    const index = this.customers.findIndex(c => c.id === response.data.id);
                    if (index !== -1) {
                        this.customers.splice(index, 1, response.data);
                    }
                    this.closeModal();
                    this.resetForm();
                })
                .catch(error => {
                    console.error('Error updating customer:', error);
                });
        },
        async deleteCustomer(id) {
            const result = await Swal.fire({
                title: 'Xác nhận xóa',
                text: 'Bạn có chắc chắn muốn xóa khách hàng này?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#FF3B30',
                customClass: {
                    popup: 'ios-popup',
                    confirmButton: 'ios-button ios-button-confirm',
                    cancelButton: 'ios-button ios-button-cancel',
                }
            });
            
            if (result.isConfirmed) {
                axios.delete(`/api/customers/${id}`)
                    .then(() => {
                        this.customers = this.customers.filter(customer => customer.id !== id);
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: 'Xóa khách hàng thành công!',
                            timer: 3000,
                            customClass: { popup: 'ios-popup' }
                        });
                    })
                    .catch(error => {
                        console.error('Error deleting customer:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Có lỗi xảy ra khi xóa khách hàng',
                            customClass: { popup: 'ios-popup' }
                        });
                    });
            }
        },
        closeModal() {
            this.showAddCustomerModal = false;
            this.showEditCustomerModal = false;
            this.editingCustomer = null;
            this.resetForm();
        },
        resetForm() {
            this.customerForm = {
                name: '',
                email: '',
                phone_number: '',
            };
        },
    },
};
</script>
