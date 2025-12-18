<template>
  <div class="group-assignment-page">
    <div class="page-header">
      <h1 class="page-title">Group Assignment</h1>
      <p class="page-description">Assign Zalo groups to branches and departments for multi-branch support</p>
    </div>

    <!-- Filters -->
    <div class="filters-section mb-4">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Filter by Branch</label>
          <select v-model="filterBranch" class="form-select">
            <option value="">All Branches</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Filter by Department</label>
          <select v-model="filterDepartment" class="form-select">
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </option>
          </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input
              v-model="filterUnassigned"
              class="form-check-input"
              type="checkbox"
              id="filterUnassigned"
            />
            <label class="form-check-label" for="filterUnassigned">
              Show only unassigned groups
            </label>
          </div>
        </div>

        <div class="col-md-3 d-flex align-items-end">
          <button @click="resetFilters" class="btn btn-outline-secondary">
            Reset Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading groups...</p>
    </div>

    <!-- Groups Table -->
    <div v-else class="card">
      <div class="card-body">
        <div v-if="filteredGroups.length === 0" class="text-center py-5 text-muted">
          <i class="bi bi-inbox" style="font-size: 3rem;"></i>
          <p class="mt-2">No groups found matching your filters</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="width: 40%">Group</th>
                <th style="width: 20%">Branch Assignment</th>
                <th style="width: 20%">Department Assignment</th>
                <th style="width: 20%" class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="group in filteredGroups" :key="group.id">
                <!-- Group Info -->
                <td>
                  <div class="d-flex align-items-center">
                    <img
                      v-if="group.avatar"
                      :src="group.avatar"
                      :alt="group.name"
                      class="rounded-circle me-2"
                      style="width: 40px; height: 40px; object-fit: cover;"
                    />
                    <div
                      v-else
                      class="rounded-circle me-2 bg-secondary d-flex align-items-center justify-content-center"
                      style="width: 40px; height: 40px;"
                    >
                      <i class="bi bi-people text-white"></i>
                    </div>
                    <div>
                      <div class="fw-semibold">{{ group.name }}</div>
                      <small class="text-muted">ID: {{ group.zalo_group_id }}</small>
                    </div>
                  </div>
                </td>

                <!-- Branch Assignment -->
                <td>
                  <select
                    v-model="group.branch_id"
                    @change="markDirty(group)"
                    class="form-select form-select-sm"
                  >
                    <option :value="null">Unassigned (Global)</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                      {{ branch.name }}
                    </option>
                  </select>
                </td>

                <!-- Department Assignment -->
                <td>
                  <select
                    v-model="group.department_id"
                    @change="markDirty(group)"
                    class="form-select form-select-sm"
                  >
                    <option :value="null">None</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                      {{ dept.name }}
                    </option>
                  </select>
                </td>

                <!-- Actions -->
                <td class="text-center">
                  <button
                    @click="saveAssignment(group)"
                    :disabled="!isDirty(group) || saving === group.id"
                    class="btn btn-sm btn-primary"
                  >
                    <span v-if="saving === group.id">
                      <span class="spinner-border spinner-border-sm me-1"></span>
                      Saving...
                    </span>
                    <span v-else>
                      <i class="bi bi-check-circle me-1"></i>
                      Save
                    </span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div class="mt-3 text-muted">
          <small>
            Showing {{ filteredGroups.length }} of {{ groups.length }} groups
            <span v-if="dirtyGroups.size > 0" class="text-warning ms-2">
              <i class="bi bi-exclamation-circle"></i>
              {{ dirtyGroups.size }} unsaved change(s)
            </span>
          </small>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'GroupAssignment',

  data() {
    return {
      groups: [],
      branches: [],
      departments: [],
      dirtyGroups: new Set(),
      filterBranch: '',
      filterDepartment: '',
      filterUnassigned: false,
      loading: true,
      saving: null,
    };
  },

  computed: {
    filteredGroups() {
      return this.groups.filter(group => {
        // Filter by unassigned
        if (this.filterUnassigned && group.branch_id !== null) {
          return false;
        }

        // Filter by branch
        if (this.filterBranch && group.branch_id !== parseInt(this.filterBranch)) {
          return false;
        }

        // Filter by department
        if (this.filterDepartment && group.department_id !== parseInt(this.filterDepartment)) {
          return false;
        }

        return true;
      });
    },
  },

  methods: {
    async fetchData() {
      try {
        this.loading = true;

        // Fetch groups
        const groupsResponse = await axios.get('/api/zalo/groups/list-for-assignment');
        this.groups = groupsResponse.data.data;

        // Fetch branches
        const branchesResponse = await axios.get('/api/branches');
        this.branches = branchesResponse.data.data;

        // Fetch departments
        const deptsResponse = await axios.get('/api/departments');
        this.departments = deptsResponse.data.data;

        this.loading = false;
      } catch (error) {
        console.error('Failed to fetch data:', error);
        this.$toast.error('Failed to load groups and assignments');
        this.loading = false;
      }
    },

    markDirty(group) {
      this.dirtyGroups.add(group.id);
      this.$forceUpdate(); // Force re-render to update unsaved changes count
    },

    isDirty(group) {
      return this.dirtyGroups.has(group.id);
    },

    async saveAssignment(group) {
      try {
        this.saving = group.id;

        await axios.put(`/api/zalo/groups/${group.id}/assign`, {
          branch_id: group.branch_id,
          department_id: group.department_id,
        });

        this.dirtyGroups.delete(group.id);
        this.$forceUpdate(); // Force re-render
        this.$toast.success('Group assigned successfully');
      } catch (error) {
        console.error('Failed to assign group:', error);
        this.$toast.error(error.response?.data?.message || 'Failed to assign group');
      } finally {
        this.saving = null;
      }
    },

    resetFilters() {
      this.filterBranch = '';
      this.filterDepartment = '';
      this.filterUnassigned = false;
    },
  },

  mounted() {
    this.fetchData();
  },
};
</script>

<style scoped>
.group-assignment-page {
  padding: 20px;
}

.page-header {
  margin-bottom: 30px;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 8px;
}

.page-description {
  color: #6c757d;
  margin-bottom: 0;
}

.filters-section {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
}

.table th {
  font-weight: 600;
  font-size: 14px;
}

.table td {
  vertical-align: middle;
}
</style>
