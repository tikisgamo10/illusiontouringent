<template>
    <div class="card mb-3" id="events-table">
        <div class="card-header">
            <i class="fa fa-table"></i> Events
            <a class="float-right text-dark" href="/admin/events/create">New Event</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <div class="row p-2 pb-3" id="table-header-info">
                    <div class="col-4">
	                    <span class="pl-3 align-text-button pr-1">Show</span>
	                    <button class="btn btn-outline-dark dropdown-toggle" type="button" data-toggle="dropdown">
		                    {{showing}}
	                    </button>
	                    <span class="pl-1 align-text-button">events</span>
	                    <div class="dropdown-menu">
		                    <button class="dropdown-item" @click="showing = 5">5</button>
		                    <button class="dropdown-item" @click="showing = 10">10</button>
		                    <button class="dropdown-item" @click="showing = 15">15</button>
		                    <button class="dropdown-item" @click="showing = 20">20</button>
		                    <button class="dropdown-item" @click="showing = 25">25</button>
	                    </div>
                    </div>
	                <div class="input-group col-4" >
		                <input id="search-query" type="text" class="form-control" placeholder="Search for an event..." v-model="query">
		                <span class="input-group-btn">
                            <button id="search-submit" class="btn btn-dark" type="button" @click="queryEvents(query)">Search</button>
                        </span>
	                </div>
	                <div class="col-3 ml-auto">Showing {{firstRecord}} to {{lastRecord}} of {{eventsQueried.length}} events</div>
                </div>
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>
                            Event Name
                            <i class="fa fa-arrow-down float-right" v-bind:class="{'text-dark': eventDesc}" @click="sortEvents('-name')"></i>
                            <i class="fa fa-arrow-up float-right" v-bind:class="{'text-dark': eventAsc}" @click="sortEvents('name')"></i>
                        </th>
                        <th>
                            City
                            <i class="fa fa-arrow-down float-right" v-bind:class="{'text-dark': cityDesc}" @click="sortEvents('-cityName')"></i>
                            <i class="fa fa-arrow-up float-right" v-bind:class="{'text-dark': cityAsc}" @click="sortEvents('cityName')"></i>
                        </th>
                        <th>
                            Venue
                            <i class="fa fa-arrow-down float-right" v-bind:class="{'text-dark': venueDesc}" @click="sortEvents('-venueName')"></i>
                            <i class="fa fa-arrow-up float-right" v-bind:class="{'text-dark': venueAsc}" @click="sortEvents('venueName')"></i>
                        </th>
                        <th>
                            Date
                            <i class="fa fa-arrow-down float-right" v-bind:class="{'text-dark': dateDesc}" @click="sortEvents('-date')"></i>
                            <i class="fa fa-arrow-up float-right" v-bind:class="{'text-dark': dateAsc}" @click="sortEvents('date')"></i>
                        </th>
                        <th>
	                        Url Clicks
	                        <i class="fa fa-arrow-down float-right" v-bind:class="{'text-dark': urlClicksDesc}" @click="sortEvents('-urlClicks')"></i>
	                        <i class="fa fa-arrow-up float-right" v-bind:class="{'text-dark': urlClicksAsc}" @click="sortEvents('urlClicks')"></i>
                        </th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                        <template v-for="event in eventsShowing">
                            <tr>
	                            <td><a class="text-dark" :href="'/admin/events/' + event.id + '/edit'">{{event.name}}</a></td>
	                            <td><a class="text-dark" :href="'/admin/cities/' + event.city.id + '/edit'">{{event.cityName}}</a></td>
	                            <td>{{event.venueName}}</td>
	                            <td>{{event.dateFormatted}}</td>
	                            <td>{{event.urlClicks}}</td>
	                            <td>
		                            <a class="p-1 settings-icon" @click.prevent="toggleEventStatus(event)">
			                            <i class="fa" :class="{'fa-toggle-off': !event.active, 'fa-toggle-on': event.active, 'text-muted': !event.active, 'text-primary': event.active }"></i>
		                            </a>
		                            <a class="p-1 settings-icon" :href="'/admin/eventTicketSeller/' + event.id"><i class="fa fa-ticket text-dark" aria-hidden="true"></i></a>
		                            <a :href="'/admin/events/' + event.id + '/edit'" class="p-1 settings-icon"><i class="fa fa-pencil-square-o text-success"></i></a>
                                    <a class="p-1 settings-icon" @click.prevent="deleteEvent(event)"><i class="fa fa-trash-o text-danger"></i></a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>Event Name</th>
                        <th>City</th>
                        <th>Venue</th>
                        <th>Date</th>
                        <th>Url Clicks</th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0 pt-1">
                        <li class="page-item" @click="previousPage" :class="{'disabled': isFirstPage}">
	                        <button class="page-link">Previous</button>
                        </li>
                        <li class="page-item" :class="{'active': leftActive}" @click="page = leftPage">
	                        <button class="page-link">{{leftPage}}</button>
                        </li>
	                    <template v-if="lastPage > 1">
		                    <li class="page-item" :class="{'active': middleActive}" @click="page = middlePage">
			                    <button class="page-link">{{middlePage}}</button>
		                    </li>
	                    </template>
                        <template v-if="lastPage > 2">
	                        <li class="page-item" :class="{'active': rightActive}" @click="page = rightPage">
		                        <button class="page-link">{{rightPage}}</button>
	                        </li>
                        </template>
                        <li class="page-item" @click="nextPage" :class="{'disabled': isLastPage}">
	                        <button class="page-link">Next</button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <div class="card-footer small text-muted">Last event created {{lastCreated}}.</div>
    </div>
</template>

<script>
    import axios from 'axios';
    import pagination from '../mixins/pagination';
    import dynamicSorting from '../mixins/dynamicSorting';
    import swal from 'sweetalert';
    export default{

        mixins: [pagination, dynamicSorting],

        created(){

            this.token = document.querySelector("meta[name='csrf-token']").content;
            axios.defaults.headers.common = {
              'X-Requested-With': 'XMLHttpRequest',
            };

            axios.get('/api/admin/events').then(response => {
                this.events = response.data;
                this.eventsQueried = response.data;
                let sort = document.querySelector("meta[name='sort']").content;
                this.sortEvents(sort);
            }, response => {
                swal('Oh no!', "An error occurred with the API", "error");
            });

            axios.get('/api/admin/events/lastCreated')
                .then(response => {
                    this.lastCreated = response.data.lastCreated;
                })
                .catch(response => {
                    swal('Oh no!', "An error occurred with the API", "error");
                });



        },
	    
        data(){
            return{
                events: [],
	            eventsQueried: [],
                sort: 'date',
	            showing: 10,
	            page: 1,
                token: '',
	            lastCreated: '',
	            query: '',
	            active: false
            }
        },

        computed: {
            eventAsc: function(){
                return (this.sort === 'name');
            },

            eventDesc: function(){
                return (this.sort === '-name');
            },

            cityAsc: function(){
                return (this.sort === 'cityName');
            },

            cityDesc: function(){
                return (this.sort === '-cityName');
            },

            dateAsc: function(){
                return (this.sort === 'date');
            },

            dateDesc: function(){
                return (this.sort === '-date');
            },

            venueAsc: function(){
                return (this.sort === 'venueName');
            },

            venueDesc: function(){
                return (this.sort === '-venueName');
            },

            urlClicksAsc: function(){
                return (this.sort === 'urlClicks');
            },

            urlClicksDesc: function(){
                return (this.sort === '-urlClicks');
            },

	        eventsShowing: function(){
                let beginning = this.showing * (this.page - 1);
                if(beginning > this.eventsQueried.length){
                    this.pageOutOfRange();
                    return this.eventsQueried.slice(this.showing* (this.lastPage - 1), this.showing* (this.lastPage - 1) + this.showing);
                }
                return this.eventsQueried.slice(beginning, beginning + this.showing);
	        },
            lastPage: function(){
                return Math.ceil(this.eventsQueried.length / this.showing);
            },
            lastRecord: function(){
                return Math.min(this.firstRecord + this.showing - 1, this.eventsQueried.length);
            }
        },

        methods: {
            sortEvents: function(property){
                this.eventsQueried.sort(this.dynamicSort(property));
                history.replaceState(null, '', '?sort=' + property);
            },

	        pageOutOfRange: function(){
                this.page = this.lastPage;
	        },
            deleteEvent: function(lj_event){
                let self = this;
                let index = this.events.indexOf(lj_event);
                let indexQ = this.eventsQueried.indexOf(lj_event);
                if(index === -1 || indexQ === -1){
                    swal("Error", "Couldn't find index of this Event", "error");
                }else{
                    swal({
                        title: "Warning",
                        text: "Delete " + lj_event.name + "?",
                        dangerMode: true,
                        buttons: true,
                        icon: 'warning'
                    }).then((willDelete) => {
                        if(willDelete){
                            axios.post('/admin/events/' + lj_event.id, {
                                _method: 'DELETE',
                                _token: this.token
                            }).then((response) => {
                                swal("Success", "Event was deleted", "success");
                                self.events.splice(index, 1);
                                self.eventsQueried.splice(indexQ, 1);
                            }).catch((response) => {
                                swal("Oh no!", "Looks like something went wrong.", "error");
                            });
                        }
                    });
                }
            },

	        toggleEventStatus: function(lj_event){
                let self = this;
                let index = this.events.indexOf(lj_event);
                let indexQ = this.eventsQueried.indexOf(lj_event);
                if(index === -1 || indexQ === -1){
                    swal("Error", "Couldn't find index of this Event", "error");
                }else{
                    axios.post('/api/events/toggle/' + lj_event.id, {
                        _method: 'PATCH',
                        _token: this.token
                    }).then((response) => {
                        console.log(response);
                        let event = self.events[index];
                        event.active = !event.active;
                        self.events.splice(index, 1, event);
                        self.eventsQueried.splice(indexQ, 1, event);
                    }).catch((response) => {
                        swal("Oh no!", "Looks like something went wrong.", "error");
                    });
                }
	        },

	        queryEvents: function(query){
                if(!query){
                    this.eventsQueried = this.events;
                    return;
                }
                let eventsFiltered;

                eventsFiltered = this.events.filter(event => {
                    let bool;
                    bool = event.name.search(new RegExp(query, "i")) !== -1;
                    bool = bool || event.cityName.search(new RegExp(query, "i")) !== -1;
                    bool = bool || event.venueName.search(new RegExp(query, "i")) !== -1;
                    return bool;
                });
                this.eventsQueried = eventsFiltered;
	        },
        }

    }
</script>

<style scoped>
    #table-header-info{
        font-size: 0.9rem;
    }
    td{
        font-size: 0.85rem;
    }

	.settings-icon:hover{
		cursor: pointer;
	}

</style>
